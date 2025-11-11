param(
    [string]$RepoUrl = "https://github.com/VGsaksham/hyperblox.git",
    [string]$Branch = "main",
    # By default, publish everything from the folder where this script lives
    [string]$SourcePath = $PSScriptRoot,
    [string]$WorkDir = "$env:TEMP\hyperblox-repo",
    [string]$GitUserName = "",
    [string]$GitUserEmail = "",
    [string]$GithubToken = $env:GITHUB_TOKEN
)

$ErrorActionPreference = "Stop"

# Begin logging to a local file so failures are visible even on double-click runs
try {
    $logPath = Join-Path -Path $PSScriptRoot -ChildPath "push-hyperblox.log"
    Start-Transcript -Path $logPath -Append -ErrorAction SilentlyContinue | Out-Null
} catch {}

function Ensure-Git {
    if (-not (Get-Command git -ErrorAction SilentlyContinue)) {
        Write-Error "git is not installed or not on PATH. Install Git for Windows first."
    }
}

function Prepare-RepoUrl {
    param([string]$Url,[string]$Token)
    if ([string]::IsNullOrWhiteSpace($Token)) { return $Url }
    $u = [Uri]$Url
    "https://$Token@$($u.Host)$($u.AbsolutePath)"
}

function Ensure-Dir {
    param([string]$Path)
    if (-not (Test-Path $Path)) {
        New-Item -ItemType Directory -Force -Path $Path | Out-Null
    }
}

function Robocopy-Tree {
    param([string]$From,[string]$To)
    # Mirror, excluding common VCS and heavy dirs
    $rc = robocopy $From $To /MIR /XD ".git" ".github" ".gitlab" "node_modules" /XF ".gitignore" ".gitattributes"
    if ($LASTEXITCODE -ge 8) { throw "robocopy failed with exit code $LASTEXITCODE" }
}

Ensure-Git

if (-not (Test-Path $SourcePath)) {
    Write-Error "SourcePath not found: $SourcePath"
}

# Clean working dir
if (Test-Path $WorkDir) { Remove-Item -Recurse -Force $WorkDir }
Ensure-Dir $WorkDir

# Clone
$AuthRepoUrl = Prepare-RepoUrl -Url $RepoUrl -Token $GithubToken
git clone $AuthRepoUrl $WorkDir
Set-Location $WorkDir

# Checkout branch (create if missing)
$hasBranch = (git ls-remote --heads origin $Branch) -ne $null
if ($hasBranch) {
    git checkout $Branch
    git pull origin $Branch
} else {
    git checkout -b $Branch
}

# Configure identity if missing
try {
    $currentName = (git config user.name) 2>$null
    $currentEmail = (git config user.email) 2>$null
} catch { }
if ([string]::IsNullOrWhiteSpace($currentName) -and -not [string]::IsNullOrWhiteSpace($GitUserName)) {
    git config user.name $GitUserName
}
if ([string]::IsNullOrWhiteSpace($currentEmail) -and -not [string]::IsNullOrWhiteSpace($GitUserEmail)) {
    git config user.email $GitUserEmail
}

# Copy project into repo working tree
Robocopy-Tree -From $SourcePath -To $WorkDir

# Ensure Git LFS is installed and track common large assets to avoid giant packfiles/timeouts
try {
    git lfs install | Out-Null
    git lfs track "*.mp4" "*.webm" "*.mov" "*.avi" "*.mkv" "*.png" "*.jpg" "*.jpeg" "*.gif" "*.webp" "*.ico" | Out-Null
    if (Test-Path ".gitattributes") {
        git add .gitattributes
    }
} catch { Write-Warning "Git LFS not available; continuing without it (push may be slow/timeout)" }

# Optional: create a basic README if repo empty
if (-not (Test-Path "$WorkDir\README.md")) {
@"
# hyperblox

Automated sync from $SourcePath on $(Get-Date -Format "yyyy-MM-dd HH:mm:ss").
"@ | Out-File -Encoding UTF8 "$WorkDir\README.md"
}

# Commit and push
git add -A
if ((git status --porcelain).Length -eq 0) {
    Write-Host "No changes to commit."
} else {
    $msg = "chore: sync from $SourcePath on $(Get-Date -Format "yyyy-MM-dd HH:mm:ss")"
    git commit -m $msg
    # Increase HTTP buffer and use HTTP/1.1 to reduce 408/sideband disconnects on large pushes
    git -c http.postBuffer=524288000 -c http.version=HTTP/1.1 push origin $Branch
    Write-Host "Pushed to $RepoUrl ($Branch)."
}

Write-Host "Done."

try { Stop-Transcript | Out-Null } catch {}

# Keep window open when started headless/double-clicked
if ($Host.Name -match 'ConsoleHost') {
    if ($PSCommandPath -ne $null) {
        Write-Host "`nLog written to: $logPath"
        Read-Host "Press Enter to close"
    }
}

