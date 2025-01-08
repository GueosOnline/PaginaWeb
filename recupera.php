<?php

//Pantalla para recuperar contraseña de cliente


require 'config/config.php';
require 'clases/clienteFunciones.php';

$db = new Database();
$con = $db->conectar();

$errors = [];

if (!empty($_POST)) {

    $email = trim($_POST['email']);

    if (esNulo([$email])) {
        $errors[] = "Debe llenar todos los campos";
    }

    if (!esEmail($email)) {
        $errors[] = "La dirección de correo no es válida";
    }

    if (empty($errors)) {
        if (emailExiste($email, $con)) {
            $sql = $con->prepare("SELECT usuarios.id, clientes.nombres FROM usuarios
			INNER JOIN clientes ON usuarios.id_cliente=clientes.id
			WHERE clientes.email LIKE ? LIMIT 1");
            $sql->execute([$email]);
            $row = $sql->fetch(PDO::FETCH_ASSOC);
            $user_id = $row['id'];
            $nombres = $row['nombres'];

            $token = solicitaPassword($user_id, $con);

            if ($token !== null) {
                require 'clases/Mailer.php';
                $mailer = new Mailer();

                $url = SITE_URL . 'reset_password.php?id=' . $user_id . '&token=' . $token;

                $asunto = "Recuperar contraseña - Tienda Representaciones Gueos";

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
            <h4>Recuperación de Contraseña</h4>
            <p>Estimado/a <strong>' . $nombres . '</strong>,</p>
            <p>Hemos recibido una solicitud para cambiar la contraseña de tu cuenta en la <strong>Tienda Representaciones Gueos</strong>.</p>
            <p>Si fuiste tú quien hizo esta solicitud, haz clic en el siguiente enlace para restablecer tu contraseña:</p>
            <a href="' . $url . '" class="reset-link">Restablecer contraseña</a>
            <p>Si no solicitaste este cambio, por favor ignora este mensaje.</p>
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

                    echo "<p><b>Correo electrónico enviado</b></p>";
                    echo "<p>Hemos enviado un correo electronico a la dirección $email para restablecer la contraseña.</p>";

                    exit;
                }
            }
        } else {
            $errors[] = "No existe una cuenta asociada a esta dirección de correo";
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

    <link href="<?php echo SITE_URL; ?>css/bootstrap.min.css" rel="stylesheet">
    <link href="css/all.min.css" rel="stylesheet">
    <link href="css/estilos.css" rel="stylesheet">
</head>

<body class="d-flex flex-column h-100 " style="background-image: url('images/FondoLogin.jpg'); background-size: cover; background-position: center; background-attachment: fixed;">

    <?php include 'header.php'; ?>

    <!-- Contenido -->
    <main class="form-login m-auto mt-5">
        <h3>Recuperar contraseña</h3>

        <?php mostrarMensajes($errors); ?>

        <form action="recupera.php" method="post" class="row g-3" autocomplete="off">

            <div class="form-floating">
                <input class="form-control" type="email" id="email" name="email" placeholder="Correo electrónico" required>
                <label for="email">Correo electrónico</label>
            </div>

            <div class="d-grid gap-3 col-12">
                <button type="submit" class="btn btn-primary">Continuar</button>
            </div>

            <div class="col-12">
                ¿No tiene cuenta? <a href="registro.php">Registrate aquí</a>
            </div>
        </form>

    </main>

    <?php include 'footer.php'; ?>

    <script src="<?php echo SITE_URL; ?>js/bootstrap.bundle.min.js"></script>

</body>

</html>