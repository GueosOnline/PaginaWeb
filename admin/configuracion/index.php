<?php

//Pantalla para mostrar el formulario de configuraciones

require '../config/config.php';
require '../../clases/cifrado.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    header('Location: ../index.php');
    exit;
}

$db = new Database();
$con = $db->conectar();

$sql = "SELECT nombre, valor FROM configuracion";
$resultado = $con->query($sql);
$datos = $resultado->fetchAll(PDO::FETCH_ASSOC);

$config = [];

foreach ($datos as $dato) {
    $config[$dato['nombre']] = $dato['valor'];
}

require '../header.php';

?>

<main>
    <div class="container-fluid px-4">
        <h1 class="mt-4">Configuración</h1>

        <form action="guarda.php" method="post">

            <ul class="nav nav-tabs" id="myTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general-tab-pane" type="button" role="tab" aria-controls="general-tab-pane" aria-selected="true">General</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="email-tab" data-bs-toggle="tab" data-bs-target="#email-tab-pane" type="button" role="tab" aria-controls="email-tab-pane" aria-selected="false">Correo electrónico</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="wp-tab" data-bs-toggle="tab" data-bs-target="#wp-tab-pane" type="button" role="tab" aria-controls="wp-tab-pane" aria-selected="false">Wompi</button>
                </li>
            </ul>

            <div class="tab-content mt-4" id="myTabContent">

                <!-- Tab General -->

                <div class="tab-pane fade show active" id="general-tab-pane" role="tabpanel" aria-labelledby="general-tab" tabindex="0">
                    <div class="row mb-3">
                        <div class="col-6">
                            <label for="nombre">Nombre</label>
                            <input class="form-control" type="text" name="nombre" id="nombre" value="<?php echo $config['tienda_nombre']; ?>">
                        </div>

                        <div class="col-6">
                            <label for="telefono">Teléfono</label>
                            <input class="form-control" type="text" name="telefono" id="telefono" value="<?php echo $config['tienda_telefono']; ?>">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-6">
                            <label for="moneda">Moneda</label>
                            <input class="form-control" type="text" name="moneda" id="moneda" value="<?php echo $config['tienda_moneda']; ?>">
                        </div>
                    </div>
                </div>

                <!-- Tab Email -->
                <div class="tab-pane fade" id="email-tab-pane" role="tabpanel" aria-labelledby="email-tab" tabindex="0">
                    <div class="row mb-3">
                        <div class="col-6">
                            <label for="smtp">SMTP</label>
                            <input class="form-control" type="text" name="smtp" id="smtp" value="<?php echo $config['correo_smtp']; ?>">
                        </div>

                        <div class="col-6">
                            <label for="puerto">Puerto</label>
                            <input class="form-control" type="text" name="puerto" id="puerto" value="<?php echo $config['correo_puerto']; ?>">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-6">
                            <label for="email">Correo</label>
                            <input class="form-control" type="email" name="email" id="email" value="<?php echo $config['correo_email']; ?>">
                        </div>
                        <div class="col-6">
                            <label for="password">Contraseña</label>
                            <input class="form-control" type="password" name="password" id="password" value="<?php echo $config['correo_password']; ?>">
                        </div>
                    </div>
                </div>

                <!-- Tab Wompi -->
                <div class="tab-pane fade" id="wp-tab-pane" role="tabpanel" aria-labelledby="wp-tab" tabindex="0">
                    <div class="row mb-3">
                        <div class="col-12">
                            <label for="wp_public">Llave Publica</label>
                            <input class="form-control" type="text" name="wp_public" id="wp_public" value="<?php echo $config['wp_public']; ?>">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-12">
                            <label for="wp_private">Llave privada</label>
                            <input class="form-control" type="text" name="wp_private" id="wp_private" value="<?php echo $config['wp_private']; ?>">
                        </div>
                    </div>
                    <hr>
                    <div class="row mb-3">
                        <div class="col-12">
                            <label for="wp_public">Eventos</label>
                            <input class="form-control" type="text" name="wp_public" id="wp_public" value="<?php echo $config['wp_events']; ?>">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-12">
                            <label for="wp_public">Integridad</label>
                            <input class="form-control" type="text" name="wp_public" id="wp_public" value="<?php echo $config['wp_integrity']; ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-6">
                    <button class="btn btn-primary" type="submit">Guardar</button>
                </div>
            </div>

        </form>

    </div>
</main>


<?php require '../footer.php'; ?>