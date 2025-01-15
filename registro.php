<?php

//Pantalla para registro de cliente
//Pantalla para registro de cliente

require 'config/config.php';
require 'clases/clienteFunciones.php';

$db = new Database();
$con = $db->conectar();

$departamentos = $con->query("SELECT id, departamento FROM departamentos")->fetchAll(PDO::FETCH_ASSOC);
$ciudades = $con->query("SELECT id, ciudad FROM ciudades")->fetchAll(PDO::FETCH_ASSOC);
$errors = [];

if (!empty($_POST)) {

    $nombres = trim($_POST['nombres']);
    $apellidos = trim($_POST['apellidos']);
    $email = trim($_POST['email']);
    $telefono = trim($_POST['telefono']);
    $cedula = trim($_POST['cedula']);
    $usuario = trim($_POST['usuario']);
    $password = trim($_POST['password']);
    $repassword = trim($_POST['repassword']);
    $departamento = $_POST['departamento']; // Este es el nombre del departamento
    $ciudad = $_POST['ciudad']; // Este es el nombre del departament
    $direccion = trim($_POST['direccion']);


    $ip = $_SERVER['REMOTE_ADDR'];
    $captcha = $_POST['g-recaptcha-response'];
    $secretkey = "6LfofqcqAAAAAFET5lfdhheMwH4E03v4UaP7mA1a"; //llave secreta

    $respuesta = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=$secretkey&response=$captcha&remoteip=$ip");

    $atributos = json_decode($respuesta, TRUE);

    if (!$atributos['success']) {
        $errors[] = "Verificar el reCAPTCHA";
    }

    if (esNulo([$nombres, $apellidos, $email, $telefono, $cedula, $usuario, $password, $repassword])) {
        $errors[] = "Debe llenar todos los campos";
    }

    if (!esEmail($email)) {
        $errors[] = "La dirección de correo no es válida";
    }

    if (!validaPassword($password, $repassword)) {
        $errors[] = "Las contraseñas no coinciden";
    }

    if (usuarioExiste($usuario, $con)) {
        $errors[] = "El nombre de usuario $usuario ya existe";
    }

    if (emailExiste($email, $con)) {
        $errors[] = "El correo electrónico $email ya existe";
    }

    if (empty($errors)) {

        $id = registraCliente([$nombres, $apellidos, $email, $telefono, $cedula, $departamento, $ciudad, $direccion], $con);

        if ($id > 0) {

            require 'clases/Mailer.php';
            $mailer = new Mailer();
            $token = generarToken();
            $pass_hash = password_hash($password, PASSWORD_DEFAULT);

            $idUsuario = registraUsuario([$usuario, $pass_hash, $token, $id], $con);
            if ($idUsuario > 0) {

                $url = SITE_URL . 'activa_cliente.php?id=' . $idUsuario . '&token=' . $token;
                $asunto = "Activar cuenta - Tienda online";

                $cuerpo = "";
                $cuerpo .= '<!DOCTYPE html>
                <html lang="es">
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <style>
                        body {
                            font-family: Arial, sans-serif;
                            margin: 0;
                            padding: 0;
                            background-color: #f9f9f9;
                        }
                
                        .container {
                            width: 100%;
                            max-width: 600px;
                            margin: 0 auto;
                            background-color: #ffffff;
                            padding: 20px;
                            border-radius: 8px;
                            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
                        }
                
                        .header {
                            text-align: center;
                            padding: 20px 0;
                        }
                
                        .header img {
                            max-width: 200px;
                        }
                
                        .content {
                            margin-top: 20px;
                            font-size: 16px;
                            color: #333;
                        }
                
                        .content h4 {
                            font-size: 22px;
                            color: #333;
                            text-align: center; 
                        }
                
                        .content p {
                            color: #555;
                            font-size: 16px;
                            line-height: 1.6;
                        }
                
                        .content .reset-link {
                            display: block;
                            margin-top: 15px;
                            padding: 10px;
                            background-color: #007bff;
                            color: #fff;
                            text-align: center;
                            text-decoration: none;
                            font-weight: bold;
                            border-radius: 5px;
                        }
                
                        .footer {
                            text-align: center;
                            margin-top: 30px;
                            font-size: 14px;
                            color: #888;
                        }
                
                        .footer p {
                            margin: 5px 0;
                        }
                
                        .custom-hr {
                            border: 0;
                            border-top: 2px solid #333;
                            margin: 20px 0;
                        }
                
                        /* Estilos para dispositivos móviles */
                        @media screen and (max-width: 600px) {
                            .container {
                                padding: 15px;
                            }
                
                            .header img {
                                max-width: 150px;
                            }
                        }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <!-- Logo -->
                        <div class="header">
                            <img src="https://es.digitaltrends.com/wp-content/uploads/2023/12/google-chrome.jpeg?resize=1000%2C600&p=1" alt="Logo de la Tienda">
                        </div>
                
                        <!-- Línea divisoria -->
                        <hr class="custom-hr">
                
                        <!-- Contenido principal -->
                        <div class="content">
                            <h4>Activación de la cuenta</h4>
                            <p>Estimado/a <strong>' . $nombres . '</strong>,</p>
                            <p>¡Gracias por registrarte en la <strong>Tienda Representaciones Gueos</strong>.</p>
                            <p>Para completar el proceso de registro y activar tu cuenta, por favor haz clic en el siguiente enlace:</p>
                            <a href="' . $url . '" class="reset-link">Activar Cuenta</a>
                            <p>Si tu no has iniciado ni completado el registro, por favor ignora este mensaje. Si tienes alguna duda o necesitas ayuda adicional, no dudes en contactarnos.</p>
                            <p>¡Gracias por formar parte de nuestra comunidad!</p>
                        </div>
                
                        <!-- Línea divisoria -->
                        <hr class="custom-hr">
                
                        <!-- Pie de página -->
                        <div class="footer">
                            <p>&copy; 2025 Representaciones Gueos LTDA. Todos los derechos reservados.</p>
                        </div>
                    </div>
                </body>
                </html>';




                if ($mailer->enviarEmail($email, $asunto, $cuerpo)) {
                    echo "<script>
                        alert('Para terminar el proceso de registro, siga las instrucciones que le hemos enviado a la dirección de correo electrónico $email');
                        window.location.href = 'login.php';
                      </script>";
                    exit;
                }
            } else {
                $errors[] = "Error al registrar usuario";
            }
        } else {
            $errors[] = "Error al registrar cliente";
        }
    }
}


?>
<!DOCTYPE html>
<html lang="es" class="h-100">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tienda en linea</title>

    <!--Implemntacion de recaptcha-->
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>

    <link href="<?php echo SITE_URL; ?>css/bootstrap.min.css" rel="stylesheet">
    <link href="css/all.min.css" rel="stylesheet">
    <link href="css/estilos.css" rel="stylesheet">
</head>

<body class="body-login" style="background-image: url('images/FondoLogin.jpg'); background-size: cover; background-position: center; background-attachment: fixed;">

    <?php include 'header.php'; ?>

    <!-- Contenido -->
    <main class="flex-shrink-0">
        <div class="container">
            <div class="d-flex justify-content-center mb-2">
                <i class="fas fa-book fa-4x"></i>
            </div>
            <h3 class="text-center">Datos de registro</h3>

            <?php mostrarMensajes($errors); ?>

            <form class="row g-3 justify-content-center mt-4" action="registro.php" method="post" autocomplete="off">

                <div class="row w-100 justify-content-center">
                    <div class="col-md-3">
                        <label for="nombres"><span class="text-danger">*</span> Nombres</label>
                        <input type="text" name="nombres" id="nombres" class="form-control" value="<?php echo isset($_POST['nombres']) ? $_POST['nombres'] : ''; ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label for="apellidos"><span class="text-danger">*</span> Apellidos</label>
                        <input type="text" name="apellidos" id="apellidos" class="form-control" value="<?php echo isset($_POST['apellidos']) ? $_POST['apellidos'] : ''; ?>" required>
                    </div>
                </div>

                <div class="row w-100 justify-content-center">
                    <div class="col-md-3">
                        <label for="email"><span class="text-danger">*</span> Correo electrónico</label>
                        <input type="email" name="email" id="email" class="form-control" value="<?php echo isset($_POST['email']) ? $_POST['email'] : ''; ?>" required>
                        <span id="validaEmail" class="text-danger"></span>
                    </div>
                    <div class="col-md-3">
                        <label for="telefono"><span class="text-danger">*</span>Celular</label>
                        <input type="tel" name="telefono" id="telefono" class="form-control" value="<?php echo isset($_POST['telefono']) ? $_POST['telefono'] : ''; ?>" oninput="this.value = this.value.replace(/[^0-9]/g, '');" pattern="^[1-9]{1}[0-9]{9}$" title="No es un numero valido. El telefono debe tener 10 dígitos y no comenzar con 0." required>
                    </div>
                </div>

                <div class="row w-100 justify-content-center">
                    <div class="col-md-3">
                        <label for="cedula"><span class="text-danger">*</span> Cedula</label>
                        <input type="text" name="cedula" id="cedula" class="form-control" value="<?php echo isset($_POST['cedula']) ? $_POST['cedula'] : ''; ?>" oninput="this.value = this.value.replace(/[^0-9]/g, '');" pattern="^[1-9]{1}[0-9]{5,9}$" title="No es un numero valido." required>
                    </div>
                    <div class="col-md-3">
                        <label for="usuario"><span class="text-danger">*</span> Usuario</label>
                        <input type="text" name="usuario" id="usuario" class="form-control" value="<?php echo isset($_POST['usuario']) ? $_POST['usuario'] : ''; ?>" required>
                        <span id="validaUsuario" class="text-danger"></span>
                    </div>
                </div>

                <div class="row w-100 justify-content-center">
                    <div class="col-md-3">
                        <label for="password"><span class="text-danger">*</span> Contraseña</label>
                        <input type="password" name="password" id="password" class="form-control" required>
                    </div>

                    <div class="col-md-3">
                        <label for="repassword"><span class="text-danger">*</span> Repetir contraseña</label>
                        <input type="password" name="repassword" id="repassword" class="form-control" required>
                    </div>
                </div>


                <!-- Select para Departamento -->
                <div class="row w-100 justify-content-center">
                    <div class="col-md-3">
                        <label for="departamento" class="form-label"><span class="text-danger">*</span>Departamento</label>
                        <select class="form-select" id="departamento" name="departamento" onchange="setDepartamentoNombre()" required>
                            <option value="">Selecciona un Departamento</option>
                            <?php foreach ($departamentos as $departamento) { ?>
                                <option value="<?php echo $departamento['departamento']; ?>"><?php echo $departamento['departamento']; ?></option>
                            <?php } ?>
                        </select>
                    </div>

                    <!-- Select para Ciudad (vacío inicialmente) -->
                    <div class="col-md-3">
                        <label for="ciudad" class="form-label"><span class="text-danger">*</span>Ciudad / Sector</label>
                        <select class="form-select" id="ciudad" name="ciudad" required>
                            <option value="">Selecciona una Ciudad</option>
                            <?php foreach ($ciudades as $ciudad) { ?>
                                <option value="<?php echo $ciudad['ciudad']; ?>"><?php echo $ciudad['ciudad']; ?></option>
                            <?php } ?>
                        </select>
                    </div>
                </div>

                <div class="row w-100 justify-content-center">
                    <div class="col-md-3">
                        <label for="direccion"><span class="text-danger">*</span> Direccion</label>
                        <input type="direccion" name="direccion" id="direccion" class="form-control" required>
                    </div>
                </div>
                <div class="col-md-3 text-center">
                    <i><b>Nota:</b> Los campos con asterisco son obligatorios</i>
                    <hr>
                </div>
                <div class="row w-100 justify-content-center d-flex align-items-center">

                    <div class="col-md-3 text-center ">
                        <div class="g-recaptcha" data-sitekey="6LfofqcqAAAAACQ8aKiBzA9vzFCSEK1FcFwoJD2J"></div> <!--llave publica-->
                    </div>

                    <div class="col-md-3 text-center align-items-center ">
                        <button type="submit" class="btn btn-primary" style="width: 50%;">Registrar</button>
                    </div>
                </div>
            </form>

        </div>
    </main>

    <?php include 'footer.php'; ?>

    <script src="<?php echo SITE_URL; ?>js/bootstrap.bundle.min.js"></script>

    <script>
        let txtUsuario = document.getElementById('usuario')
        txtUsuario.addEventListener("blur", function() {
            existeUsuario(txtUsuario.value)
        }, false)

        let txtEmail = document.getElementById('email')
        txtEmail.addEventListener("blur", function() {
            existeEmail(txtEmail.value)
        }, false)

        function existeEmail(email) {
            let url = "clases/clienteAjax.php"
            let formData = new FormData()
            formData.append("action", "existeEmail")
            formData.append("email", email)

            fetch(url, {
                    method: 'POST',
                    body: formData
                }).then(response => response.json())
                .then(data => {

                    if (data.ok) {
                        document.getElementById('email').value = ''
                        document.getElementById('validaEmail').innerHTML = 'Email no disponible'
                    } else {
                        document.getElementById('validaEmail').innerHTML = ''
                    }

                })
        }

        function existeUsuario(usuario) {
            let url = "clases/clienteAjax.php"
            let formData = new FormData()
            formData.append("action", "existeUsuario")
            formData.append("usuario", usuario)

            fetch(url, {
                    method: 'POST',
                    body: formData
                }).then(response => response.json())
                .then(data => {

                    if (data.ok) {
                        document.getElementById('usuario').value = ''
                        document.getElementById('validaUsuario').innerHTML = 'Usuario no disponible'
                    } else {
                        document.getElementById('validaUsuario').innerHTML = ''
                    }

                })
        }
    </script>

</body>

</html>