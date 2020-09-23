<!DOCTYPE html>
<html lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">

		<title>SIMAS BINA RAHAYU</title>
		
		<link href="assets/template/vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
	
		<link href="assets/template/vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">
		
		<link href="assets/template/vendors/nprogress/nprogress.css" rel="stylesheet">
		
		<link href="assets./template/vendors/animate.css/animate.min.css" rel="stylesheet">

        <link href="assets/template/build/css/login.css" rel="stylesheet">

	</head>

    <body class="login">
		<div>
			<a class="hiddenanchor" id="signup"></a>
			<a class="hiddenanchor" id="signin"></a> 
			
			<div class="login_wrapper">
			<?php
			if(isset($_GET['pesan'])){
				if($_GET['pesan'] == "gagal"){
					echo "<div style='margin-bottom:-55px' class='alert alert-danger' role='alert'><span class=' glyphicon glyphicon-warning-sign'></span> Gagal  Login, Username atau Password salah! </div>";
					 }
				}
			?>
				<div class="animate form login_form">
					<section class="login_content">
						<form action="act_login.php" method="post">
						<h2><b>- SELAMAT DATANG -</b></h2>
							<h1><b>SISTEM INFORMASI MANAJEMEN ARSIP SURAT</b></h1>
							<br/>
							<h2><b>Silahkan Login</b></h2>
							<div>
								<input type="text" class="form-control" name="username" placeholder="Masukkan Username" required="" />
							</div>
							<div>
								<input type="password" class="form-control" name="password" placeholder="Masukkan Password" required="" />
							</div>
							<div>
								<button class="btn btn-default" type="reset">Reset </button>
								<button class="btn btn-success" type="submit" name="login">Login</button>
							</div>
							<div class="clearfix"></div>
							<br/>
							<div class="separator">
								<div>
								
									<br/>
                                    <p><b>© 2020 Sekolah Menengah Kejuruan Bina Rahayu</b></P>
                               
								</div>
							</div>
						</form>
					</section>
				</div>

				
			</div>
		</div>
	</body>
</html>
