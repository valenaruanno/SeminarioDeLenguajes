<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/vendor/autoload.php';

$app = AppFactory::create();
$app -> addBodyParsingMiddLeware();
$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, true, true);
$app->add( function ($request, $handler) {
    $response = $handler->handle($request);

    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'OPTIONS, GET, POST, PUT, PATCH, DELETE')
        ->withHeader('Content-Type', 'application/json')
    ;
});

// ACÁ VAN LOS ENDPOINTS
//$app = AppFactory::create();
//$app -> addBodyParsingMiddleware();
// conexion a la base de datos 
function getConnection (){
    $dbhost = "db";
    $dbname = "seminariophp";
    $dbuser = "seminariophp";
    $dbpass = "seminariophp";

    $connection = new PDO ("mysql:host=$dbhost;dbname=$dbname",$dbuser,$dbpass);
    $connection ->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
   
    return $connection;
}
                                /*  LOCALIDADES */
// CREAR -> insert
$app -> post ("/localidades", function (Request $request, Response $response){
    $data = $request->getParsedBody();
    if (empty ( $data ['nombre'])){
        $payload = json_encode ([
            "status" => "Error",
            "code" => 400,
            "data" => "El campo es requerido"
        ]);
        $response->getBody()->write ($payload);
        return $response->withStatus(400);
    } else{
        $nombre = $data ['nombre'];
        if (strlen($nombre) < 50){
            try{
                $conn = getConnection();
                $consulta_repetido = $conn-> prepare ("SELECT * FROM localidades WHERE nombre = ?");
                $consulta_repetido->execute ([$nombre]);


                if ($consulta_repetido-> rowCount () > 0){
                    $response->getBody()->write (json_encode (["Error" => "El campo no se puede repetir"]));
                    return $response->withStatus(400);
                } else{
                    $sql = "INSERT INTO localidades (nombre) VALUES (:nombre)";
                    $consulta = $conn-> prepare($sql);
                    $consulta-> bindvalue(":nombre", $nombre);
                    $consulta->execute();


                    $response->getBody()->write(json_encode(["message" => "Localidad agregada"]));
                    return $response->withStatus(201);
                }
            } catch (\Exception $e){
                //se produjo un error al insertar
                $response->getBody()->write (json_encode(["Error" => "Error inesperado"]));
                return $response->withStatus(500);
            }
        }else {
            $response->getBody()->write (json_encode (["Error" => "El nombre de la localidad debe tener menos de 50 caracteres"]));
            return $response->withStatus(400);
        }
    }
});


// EDITAR -> update


$app -> put ("/localidades/{id}", function (Request $request, Response $response,$args){
    $id = $args['id']; //Obtengo como parametro el id de la localidad a editar
    $data = $request->getParsedBody();
    if (empty ($data['nombre'])){
        $response->getBody()->write(json_encode (["Error" => "El campo es requerido"]));
        return $response->withStatus(400);
    } else{
        $nombre = $data['nombre'];
        if (strlen ($nombre) < 50){
            try{
                $conn = getConnection();


                $consulta_localidad = $conn->prepare("SELECT * FROM localidades WHERE id = ?");
                $consulta_localidad-> execute([$id]);
                if ($consulta_localidad->rowCount() == 0){
                    $response->getBody()->write(json_encode (["Error" => "La localidad a modificar no existe."]));
                    return $response->withStatus(400);  
                } else {
                    //verifica si el nuevo nombre esta duplicado
                    $consulta_repetido = $conn->prepare("SELECT * FROM localidades WHERE nombre = ? AND id != ?");
                    $consulta_repetido->execute([$nombre, $id]);
                    if ($consulta_repetido-> rowCount() > 0){
                        $response->getBody()->write(json_encode (["Error" => "El nuevo nombre de la localidad ya esta en uso"]));
                        return $response->withStatus(400);
                    } else{
                        //actualizar la localidad
                        $sql = "UPDATE localidades SET nombre = :nombre WHERE id= :id";
                        $consulta = $conn->prepare ($sql);
                        $consulta->bindValue(":nombre", $nombre);
                        $consulta->bindValue(":id", $id);
                        $consulta->execute();
                        $response->getBody()->write(json_encode(["Localidad actualizada correctamente"]));
                        return $response->withStatus(200);
                    }
                }
            } catch(\Exception $e){
                $response->getBody()->write(json_encode(["Error" => "Error inesperado"]));
                return $response->withStatus(500);
            }
        }else{
            $response->getBody()->write(json_encode(["Message" => "El nombre de la localidad debe tener menos de 50 caracteres"]));
            return $response->withStatus(400);
        }
    }
});
//ELIMINAR


$app -> delete("/localidades/{id}", function (Request $request, Response $response, $args){
    $data = $args['id'];
    try {
        $conn = getConnection ();


        //verificar si el id esta siendo utilizado en la tabla de propiedades
        $consulta = $conn->prepare("SELECT * FROM propiedades WHERE localidad_id = ?");
        $consulta-> execute([$data]);
        if ($consulta->rowCount() > 0){
            $payload = json_encode ([
                "status" => "Error",
                "code" => 400,
                "data" => "El id que se desea eliminar esta siendo utilizado en la tabla propiedades"
            ]);
            $response-> getBody()->write($payload);
            return $response->withStatus(400);
        } else{
            $sql = "DELETE FROM localidades WHERE id = ?";
            $consulta = $conn->prepare($sql);
            $consulta->execute([$data]);
            $payload = json_encode([
                "status" => "success",
                "code" => 200,
                "data" => "La localidad fue eliminada correctamente"
            ]);
            $response-> getBody()->write($payload);
            return $response->withStatus(200);
        }
    } catch (PDOException $e){
        $payload = json_encode([
            "status" => "Error",
            "code" => 500,
            "data" => "Error inesperado"
        ]);
        $response-> getBody()->write($payload);
        return $response->withStatus(500);
    }
});
//LISTAR -> select
$app -> get("/localidades", function(Request $request, Response $response){
    // try y catch lo usamos soolo para errores que no podemos controlar
    try{
        $connection = getConnection();  //obtiene la conexion con la  base de datos
        $query = $connection->query ("SELECT * FROM localidades");  //completar la tabla de donde se quiere obtener
        $tipos = $query->fetchAll(PDO::FETCH_ASSOC);
        $payload = json_encode([
            "status" => "succes",
            "code" => "200",
            "data" => $tipos
        ]);


        $response->getBody()->write($payload);
        return $response->withStatus(200);
    } catch (PDOException $e){
        $payload = json_encode ([
            "status" => "Error",
            "code" => 500,
        ]);


        $response->getBody()->write($payload);
        return $response->withStatus(500);
    }
});






                                /*  TIPOs PROPIEDADES   */
//CREAR
$app-> post ("/tipos_propiedad", function (Request $request, Response $response){
    // validamos que el tipo de propiedad todavia no se encuentra agregado
    $data = $request-> getParsedBody();   //obtengo un arreglo con los datos enviados en la solicitud post
    // Chequeo si enviaron un campo nombre en la solicitud
   if (empty($data['nombre'])){  
        $payload = json_encode([
            "status" => "Error",
            "code" => 400,
            "data" => "debe ingresar el tipo de propiedad"
        ]);      
        $response -> getBody()->write($payload);
        return $response->withStatus(400);
    }else{
        $nombre = $data ['nombre'];
        if (strlen ($nombre) < 50){
            try{
                $conn = getConnection();
                $sql = "SELECT * FROM tipo_propiedades WHERE nombre = ?";
                $consulta_repetido = $conn->prepare($sql);
                $consulta_repetido->execute([$nombre]);
                if ($consulta_repetido->rowCount () > 0){
                    $payload = json_encode([
                        "status" => "Error",
                        "code" => 400,
                        "data" => "ya existe ese tipo de propiedad"
                    ]);
                    $response-> getBody()->write($payload);
                    return $response->withStatus(400);
                } else {
                    $sql = "INSERT INTO tipo_propiedades (nombre) VALUES (:nombre)";
                    $consulta = $conn->prepare($sql);
                    $consulta->bindValue(":nombre", $nombre);
                    $consulta->execute();
                    $payload = json_encode([
                        "status" => "success",
                        "code" => 200,
                        "data" => "Tipo de propiedad creada"
                    ]);
                    $response-> getBody()->write ($payload);
                    return $response->withStatus(200);
                }
            } catch (\Exception $e){
                //se produjo un error al insertar
                $payload = json_encode([
                    "status" => "Error",
                    "code" => 500,
                    "data" => "Error inesperado"
                ]);
                $response-> getBody()->write($payload);
                return $response->withStatus(500);
            }
        } else {
            $payload = json_encode([
                "status" => "Error",
                "code" => 400,
                "data" => "El nombre del tipo de propiedad debe tener menos de 50 caracteres"
            ]);
            $response-> getBody()-> write ($payload);
            return $response->withStatus(400);
        }
    }
});
//EDITAR


$app -> put ("/tipos_propiedad/{id}", function (Request $request, Response $response, $args){
    $id = $args['id'];
    $data = $request-> getParsedbody();
    if (empty($data ['nombre'])){
        $payload = json_encode([
            "status" => "Error",
            "code" => 400,
            "data" => "El campo es requerido"
        ]);
        $response -> getBody()->write ($payload);
        return $response->withStatus(400);
    } else {
        $nombre = $data['nombre'];
        if (strlen ($nombre) < 50){
            try{
                $conn = getConnection();
                $consulta_tipos_propiedad = $conn->prepare("SELECT * FROM tipo_propiedades WHERE id = ?");
                $consulta_tipos_propiedad-> execute([$id]);
                if ($consulta_tipos_propiedad -> rowCount () == 0){
                    $payload = json_encode([
                        "status" => "Error",
                        "code" => 400,
                        "data" => "La propiedad con el id proporcionado no se encuentra cargada"
                    ]);
                    $response -> getBody()->write($payload);
                    return $response->withStatus(400);
                }else {
                    $consulta_repetido = $conn-> prepare("SELECT * FROM tipo_propiedades WHERE nombre = ? and id != ?");
                    $consulta_repetido-> execute([$nombre, $id]);
                    if ($consulta_repetido-> rowCount() > 0){
                        $payload = json_encode([
                            "status" => "Error",
                            "code" => 400,
                            "data" => "El nombre de la propiedad ya esta en uso"
                        ]);
                        $response -> getBody()->write($payload);
                        return $response->withStatus(400);
                    } else{
                        $sql = "UPDATE tipo_propiedades SET nombre = :nombre WHERE id = :id";
                        $consulta = $conn->prepare($sql);
                        $consulta->bindValue(":nombre", $nombre);
                        $consulta->bindValue(":id", $id);
                        $consulta->execute();
                        $payload = json_encode([
                            "status" => "success",
                            "code" => 200,
                            "data" => "Tipo de propiedad actualizada correctamente"
                        ]);
                        $response->getBody()->write($payload);
                        return $response->withStatus(200);
                    }
                }
            } catch (\Exception $e){
                $payload = json_encode([
                    "status" => "Error",
                    "code" => 500,
                    "data" => "Error inesperado"
                ]);
                $response -> getBody()->write ($payload);
                return $response->withStatus(500);
            }
        } else{
            $payload = json_encode([
                "status" => "Error",
                "code" => 400,
                "data" => "El nombre debe tener menos de 50 caracteres"
            ]);
            $response -> getBody()->write($payload);
            return $response->withStatus(400);
        }
    }
});
//ELIMINAR
$app ->delete("/tipos_propiedad/{id}", function (Request $request, Response $response, $args){
    $id = $args['id'];
    try {
        $conn = getConnection ();
        //verificar si el id esta siendo utilizado en la tabla de propiedades
        $sql = "SELECT * FROM propiedades WHERE tipo_propiedad_id = ?";
        $consulta = $conn->prepare($sql);
        $consulta-> execute([$id]);
        if ($consulta->rowCount() > 0){
            $payload = json_encode ([
                "status" => "Error",
                "code" => 400,
                "data" => "El id que se desea eliminar esta siendo utilizado en la tabla propiedades"
            ]);
            $response-> getBody()->write($payload);
            return $response->withStatus(400);
        } else{
            $sql = "DELETE FROM tipo_propiedades WHERE id = ?";
            $consulta = $conn->prepare($sql);
            $consulta->execute([$id]);
            $payload = json_encode([
                "status" => "success",
                "code" => 200,
                "data" => "La localidad fue eliminada correctamente"
            ]);
            $response-> getBody()->write($payload);
            return $response->withStatus(200);
        }
    } catch (PDOException $e){
        $payload = json_encode([
            "status" => "Error",
            "code" => 500,
            "data" => "Error inesperado"
        ]);
        $response-> getBody()->write($payload);
        return $response->withStatus(500);
    }
});
//LISTAR -> select
$app -> get("/tipos_propiedad", function (Request $request, Response $response) {
    try {
        $connection = getConnection (); //obtiene la conexion a la base de datos
        $consulta = $connection->prepare ("SELECT * FROM tipo_propiedades");
	    $consulta->execute();
        $tipos = $consulta->fetchAll (PDO::FETCH_ASSOC);
        $payload = json_encode ([
            "status" => "success",
            "code" => "200",
            "data" => $tipos
        ]);
        $response->getBody()->write($payload);
        return $response->withStatus(200);
    } catch (PDOException $e) {
        $payload = json_encode ([
            "status" => "Error",
            "code" => 500,
        ]);
        $response->getBody()->write($payload);
        return $response->withStatus(500);
    }
});




                                /*  INQUILINO*/




//CREAR


function ValidarInquilinos ($data){
    $vector = array();
    $ok;
    if (empty($data["apellido"]))
        $vector [] = "Debe ingresarse el apellido";
    if (empty($data["nombre"]))
        $vector[] = "Debe ingresarse el nombre";
    if (empty($data['documento']))
        $vector [] = "Debe ingresarse el documento";
    if (empty($data["email"]))
        $vector [] = "Debe ingresarse el email";
    if (!isset($data['activo']))
        $vector []= "Debe ingresar si se encuentra activo o no";
    if ($vector == null)
        $ok = true;  
    else
        $ok=false;
    $resultado = array ('ok'=> $ok,'vector'=> $vector);
    return $resultado;    
}
function Requerimientos ($apellido, $nombre, $documento, $email, $activo){
    $ok;
    $vector = array();
    if (!(strlen ($apellido) < 20))
        $vector [] = "El apellido debe tener menos de 20 caracteres";
    if (!(strlen ($nombre) < 15))
        $vector[] = "El nombre debe tener menos de 15 caracteres";
    if (!is_int($documento))
        $vector [] = "El campo documento debe ser de tipo numerico entero";
    if (!(strlen($email) < 25))
        $vector [] = "El email debe tener menos de 25 caracteres";
    if (!is_int($activo))
        $vector [] = "Activo debe ingresarse en formato 1 si se encuentra activo o 0 de lo contrario";
    if ($vector == null)
        $ok = true;
    else
        $ok= false;    
    $resultado = array ('ok' => $ok, 'vector' => $vector);
    return $resultado;
}


$app-> post ("/inquilinos", function (Request $request, Response $response){
    //validamos que el inquilino no se encuentre agregado
    $data = $request->getParsedBody();
    $resultado = validarInquilinos ($data); //chequeamos que nos hayan enviado todos los campos requeridos para crear
    if ($resultado['ok'] == false){
        $string = "";
        foreach ($resultado['vector'] as $aux)
            $string .= $aux . ", ";
        $payload = json_encode([
            "status" => "Error",
            "code" => 400,
            "data" => $string
        ]);
        $response->getBody()->write($payload);
        return $response->withStatus(400);
    }else{
        $apellido = $data['apellido'];
        $nombre = $data['nombre'];
        $email = $data['email'];
        $activo = $data['activo'];
        $documento = $data['documento'];
        $requerimientos = Requerimientos($apellido, $nombre, $documento, $email, $activo);
        if ($requerimientos['ok']){
            try{
                $conn = getConnection();
                    //agregar las demas condiciones
                $consulta_repetido = $conn->prepare ("SELECT * FROM inquilinos WHERE documento = ? ");
                $consulta_repetido->execute([$documento]);
                if ($consulta_repetido ->rowCount () > 0){
                    $payload = json_encode([
                        "status" => "Error",
                        "code" => 400,
                        "data" => "Los campos ya se encuentran en uso"
                    ]);
                    $response -> getBody()->write ($payload);
                    return $response->withStatus(400);
                }else{
                    $sql = "INSERT INTO inquilinos (apellido, nombre, documento, email, activo) VALUES (:apellido, :nombre, :documento, :email, :activo)";
                    $consulta = $conn->prepare($sql);
                    $consulta-> bindValue(":apellido", $apellido);
                    $consulta->bindValue(":nombre", $nombre);
                    $consulta->bindValue (":email", $email);
                    $consulta->bindValue(":activo", $activo);
                    $consulta->bindValue(":documento", $documento);
                    $consulta->execute();
                    $payload = json_encode([
                        "status" => "success",
                        "code" => 200,
                        "data" => "Inquilino agregado correctamente"
                    ]);
                    $response-> getBody()->write($payload);
                    return $response->withStatus(200);
                }
            } catch (\Exeption $e){
                $payload = json_encode([
                    "status" => "Error",
                    "code" => 500,
                    "data" => "Error inesperado"
                ]);
                $response = getBody()->write($payload);
                return $response->withStatus(500);
            }
        }else{
            $string = "";
            foreach ($requerimientos['vector'] as $aux)
                $string .= $aux . ", ";
            $payload = json_encode([
                "status" => "Error",
                "code" => 400,
                "data" => $string
            ]);
            $response->getBody()->write($payload);
            return $response->withStatus(400);
        }
    }
});


//EDITAR
$app-> put ("/inquilinos/{id}", function (Request $request, Response $response,$args){
    $id = $args['id'];
    $data = $request-> getParsedBody();
    $resultado = ValidarInquilinos ($data);
    if ($resultado['ok'] == false){
        $string = " ";
        foreach ($resultado['vector'] as $aux)
            $string .= $aux .  ", ";
        $payload = json_encode([
            "status" => "Error",
            "code" => 400,
            "data" => $string
        ]);
        $response->getBody()->write($payload);
  return $response->withStatus(400);
    } else{
        $apellido = $data['apellido'];
        $nombre = $data['nombre'];
        $email = $data['email'];
        $documento = $data['documento'];
        $activo = $data['activo'];
        $requerimientos = Requerimientos($apellido, $nombre, $documento, $email, $activo);
        if ($requerimientos['ok'] == true){
            try{
                $conn = getConnection();
                $consulta_repetido = $conn->prepare ("SELECT * FROM inquilinos WHERE documento = ?");
                $consulta_repetido->execute([$documento]);
                if ($consulta_repetido ->rowCount () > 0){
                    $payload = json_encode ([
                        "status" => "Error",
                        "code" => "400",
                        "data" => "El documento ya se encuentra en uso"
                    ]);
                    $response -> getBody()->write ($payload);
                    return $response->withStatus(400);
                } else {
                    $consulta = $conn->prepare ("SELECT * FROM inquilinos WHERE id = ?");
                    $consulta->execute([$id]);
                    if ($consulta-> rowCount () > 0){
                        $sql = "UPDATE inquilinos SET apellido = :apellido, nombre = :nombre, documento = :documento, email = :email, activo = :activo WHERE id = :id";
                        $consulta = $conn->prepare($sql);
                        $consulta->bindValue(":id", $id);
                        $consulta->bindValue(":apellido", $apellido);
                        $consulta->bindValue(":nombre", $nombre);
                        $consulta->bindValue(":documento", $documento);
                        $consulta->bindValue(":email", $email);
                        $consulta->bindValue(":activo", $activo);  
                        $consulta-> execute();
                        $payload = json_encode ([
                            "status" => "success",
                            "code" => 200,
                            "data" => "Inquilino actualizado correctamente"
                        ]);
                        $response->getBody()->write($payload);
                        return $response->withStatus(200);
                    } else {
                        $payload = json_encode ([
                            "status" => "Error",
                            "code" => 400,
                            "data" => "El id que desea editar no se encuentra cargado"
                        ]);
                        $response-> getBody()->write($payload);
                        return $response->withStatus(400);
                    }
                }
            } catch (\Exeption $e){
                $payload = json_encode ([
                    "status" => "Error",
                    "code" => 500
                ]);
                $response->getBody()->write($payload);
                return $response->withStatus(500);
            }
        } else{
            $string = " ";
            foreach ($requerimientos['vector'] as $aux)
                $string .= $aux .  ", ";
            $payload = json_encode ([
                "status" => "Error",
                "code" => 400,
                "data" => $string
            ]);
            $response->getBody()->write($payload);
            return $response->withStatus(400);
        }
    }
});


//ELIMINAR


$app ->delete("/inquilinos/{id}", function (Request $request, Response $response, $args){
    $data = $args['id'];
    try {
        $conn = getConnection ();
        //verificar si el id esta siendo utilizado en la tabla de reservas
        $sql = "SELECT * FROM reservas WHERE inquilino_id = ?";
        $consulta = $conn->prepare($sql);
        $consulta-> execute([$data]);
        if ($consulta->rowCount() > 0){
            $payload = json_encode ([
                "status" => "Error",
                "code" => 400,
                "data" => "El id que se desea eliminar esta siendo utilizado en la tabla de reservas"
            ]);
            $response-> getBody()->write($payload);
            return $response->withStatus(400);
        } else{
            $sql = "DELETE FROM inquilinos WHERE id = ?";
            $consulta = $conn->prepare($sql);
            $consulta->execute([$data]);
            $payload = json_encode([
                "status" => "success",
                "code" => 200,
                "data" => "El inquilino fue eliminado correctamente"
            ]);
            $response-> getBody()->write($payload);
            return $response->withStatus(200);
        }
    } catch (PDOException $e){
        $payload = json_encode([
            "status" => "Error",
            "code" => 500,
            "data" => "Error inesperado"
        ]);
        $response-> getBody()->write($payload);
        return $response->withStatus(500);
    }
});
//LISTAR -> select
$app-> get("/inquilinos", function (Request $request, Response $response){
    try {
        $connection = getConnection (); //Obtiene la conexion a la base de datos
        $consulta = $connection->prepare("SELECT * FROM inquilinos");
        $consulta->execute();
        $tipos = $consulta->fetchAll(PDO::FETCH_ASSOC);
        $payload = json_encode ([
            "status" => "success",
            "code" => "200",
            "data" => $tipos
        ]);
        $response->getBody()->write($payload);
        return $response->withStatus(200);
    } catch (PDOException $e) {
        $payload = json_encode ([
            "status" => "Error",
            "code" => 500,
        ]);
        $response->getBody()->write($payload);
        return $response->withStatus(500);
    }
});
//VER INQUILINO
$app -> get ("/inquilinos/{id}", function (Request $request, Response $response, $args){
    $id = $args['id'];
    try{
        $conn = getConnection();
        $consulta = $conn->prepare("SELECT * FROM inquilinos WHERE id = ?");
        $consulta->execute([$id]);
        $tipos = $consulta->fetchAll(PDO::FETCH_ASSOC);
        $payload = json_encode ([
            "status" => "success",
            "code" => "200",
            "data" => $tipos
        ]);
        $response->getBody()->write($payload);
        return $response->withStatus(200);
    } catch (PDOException $e){
        $payload = json_encode ([
            "status" => "Error",
            "code" => 500,
        ]);
        $response->getBody()->write($payload);
        return $response->withStatus(500);
    }
});
//HISTORIAL INQUILINO
$app-> get("/inquilinos/{idInquilino}/reservas", function (Request $request, Response $response, $args){
    $id = $args['idInquilino'];
    $conn = getConnection();
    $consulta = $conn->prepare("SELECT * FROM inquilinos WHERE id = ?");
    $consulta->execute([$id]);
    if ($consulta->rowCount() > 0){
        try{
            $consulta = $conn->prepare("SELECT * FROM reservas WHERE inquilino_id = ?");
            $consulta->execute([$id]);
            $tipos = $consulta->fetchAll(PDO::FETCH_ASSOC);
            if ($tipos!= null) {
                $payload = json_encode ([
                    "status" => "success",
                    "code" => "200",
                    "data" => $tipos
                ]);
                $response->getBody()->write($payload);
                return $response->withStatus(200);
            }
            else {
                $payload = json_encode ([
                    "status" => "success",
                    "code" => "400",
                    "data" => "El inquilino no posee reservas "
                ]);
                $response->getBody()->write($payload);
                return $response->withStatus(400);
            }
        }catch (PDOException $e){
            $payload = json_encode ([
                "status" => "Error",
                "code" => 500,
            ]);
            $response->getBody()->write($payload);
            return $response->withStatus(500);
        }
    } else {
        $payload = json_encode ([
            "status" => "Error",
            "code" => 400,
            "data" => "No existe el inquilino",
        ]);
        $response->getBody()->write($payload);
        return $response->withStatus(400);
    }
});
                                /*  PROPIEDADES   */
//CREAR


function ValidarPropiedad ($data){
    $vector = array();
    $ok;
    if (!empty($data["domicilio"]))
        $vector [] = "Debe ingresarse el domicilio";
    if (!empty($data["localidad_id"]))
        $vector [] = "Debe ingresarse el id de la localidad que es de la tabla de localidades";
    if (!empty($data["cantidad_huespedes"]))
        $vector[] = "Debe ingresarse la cantidad de huespedes";
    if (!empty($data["fecha_inicio_disponibilidad"]))
        $vector [] = "Debe ingresarse la fecha de disponibilidad de la propiedad";
    if (!empty($data['cantidad_dias']))
        $vector []= "Debe ingresarse la cantidad de dias";
    if (!isset($data['disponible']))
        $vector [] = "Debe ingresarse si la propiedad se encuentra disponible";
    if (!empty($data['valor_noche']))
        $vector [] = "Debe ingresarse cuanto vale hospedarse una noche";
    if (!empty($data['tipo_propiedad_id']))             //"Se elige de la tabla de tipo_propiedad" como???
        $vector [] = "Debe ingrearse el id del tipo de propiedad que es desde la tabla de tipo_propiedad";
    if ($vector == null)
        $ok = true;  
    else
        $ok=false;
    $resultado = array ('ok'=> $ok,'vector'=> $vector);
    return $resultado;    
}


function No_requeridos_propiedad ($data){
    $vector = array();
    if (empty($data['cantidad_habitaciones']))
       $vector ['cantidad_habitaciones'] = $data['cantidad_habitaciones'];
    else
       $vector ['cantidad_habitaciones'] =  0;
    if (empty($data['cantidad_banios']))
       $vector ['cantidad_banios'] =  $data['cantidad_banios'];
    else
       $vector ['cantidad_banios'] = 0;
    if (empty($data['cochera']))
       $vector ['cochera'] = $data['cochera'];
    else
       $vector ['cochera'] = 0;
    if (empty($data['imagen']))
        $vector ['imagen'] = $data['imagen'];
    else
        $vector ['imagen'] = null;
    if (empty($data['tipo_imagen']))
        $vector ['tipo_imagen'] = $data['tipo_imagen'];
    else
        $vector ['tipo_imagen'] = null;
    return $vector;
     
}
function Requerimientos_propiedad ($data, $no_requeridos, $conn){
    $ok;
    $domicilio = $data['domicilio'];
    $localidad_id = $data['localidad_id'];
    $cantidad_huespedes = $data['cantidad_huespedes'];
    $fecha_inicio_disponibilidad = $data['fecha_inicio_disponibilidad'];
    $cantidad_dias = $data['cantidad_dias'];    
    $disponible = $data['disponible'];  
    $valor_noche = $data['valor_noche'];  
    $tipo_propiedad_id = $data['tipo_propiedad_id'];
    $vector = array();
    if (strlen($domicilio) > 255)
        $vector [] = "El domicilio debe tener menos de 255 caracteres";




    $consulta_id = $conn-> prepare("SELECT id FROM localidades WHERE id = ?");
    $consulta_id ->execute([$localidad_id]);
    if ($consulta_id -> rowCount() == 0)
        $vector [] = "Debe ingresarse un id de localidad ya disponible ";
    if (!is_int ($cantidad_huespedes))
        $vector [] = "La cantidad de huespedes debe ser ingresado en formato numerico";




    if (strtotime($fecha_inicio_disponibilidad) == false)
        $vector []= "La fecha de incicio debe ser ingresada en formato fecha";
    $consulta_id = $conn-> prepare("SELECT id FROM tipo_propiedades");
    $consulta_id->execute();
    if($consulta_id-> rowCount () == 0)
        $vector[] = "Debe ingresarse un id de tipo de propiedad ya disponible";
   
    if (!is_int ($cantidad_dias))
        $vector [] = "La cantidad de dias debe ser ingresado en formato numerico";




    if (!is_int($disponible))
        $vector [] = "El campo disponible debe contener 1 si es true o  0 si es false";




    if (!is_int ($valor_noche))
        $vector [] = "El valor de la noche debe ser ingresado en formato numerico";




    if (!is_int($tipo_propiedad_id))
        $vector [] = "El tipo de propiedad debe ser ingresado en formato numerico";
    if (isset ($no_requeridos['cantidad_habitaciones'])){
        if (!is_int($no_requeridos ['cantidad_habitaciones']))
            $vector [] = "El tipo de cantidad_habitaciones debe ser ingresado debe ser ingresado en formato numerico";
    }
    if (isset ($no_requeridos['cantidad_banios'])){
        if (!is_int ($no_requeridos ['cantidad_banios']))
            $vector [] = "El tipo de cantidad_banios debe ser ingresado en formato numerico";
    }
    if (empty ($no_requeridos['cochera'])){
        if (!is_int($no_requeridos['cochera']))
            $vector [] = "El tipo de cochera debe ser ingresado debe ser ingresado en formato 1 si tiene o 0 si no tiene";
    }
    if (empty ($no_requeridos['imagen'])){
        if (!$_FILES($no_requeridos['imagen']))
            $vector [] = "La imagen de la propiedad debe ser ingresada en formato imagen";
    }
    if (empty ($no_requeridos['tipo_imagen'])){
        if (!is_string($no_requeridos['tipo_imagen']))
            $vector [] = "El tipo de imagen debe ser ingresado en formato texto";
    }
    if ($vector == null)
        $ok = true;
    else
        $ok= false;    
    $resultado = array ('ok' => $ok, 'vector' => $vector);
    return $resultado;
}








$app-> post ("/propiedades", function (Request $request,Response $response){
    //validamos que la propiedad no existe
    $data = $request->getParsedBody();
    $resultado = validarPropiedad($data);  //Chequeamos que nos hayan enviado todos los campos requeridos para crear una propiedad
    if ($resultado['ok'] == false){
        $string;
        foreach ($resultado['vector'] as $aux)
            $string .= $aux . ", ";
        $payload = json_encode([
            "status" => "Error",
            "code" => "400",
            "data" => $string
        ]);
        $response-> getBody()->write($payload);
        return $response->withStatus(400);
    } else {
        $conn = getConnection ();
        $domicilio = $data['domicilio'];
        $localidad_id = $data['localidad_id'];
        $cantidad_huespedes = $data['cantidad_huespedes'];
        $fecha_inicio_disponibilidad = $data['fecha_inicio_disponibilidad'];
        $cantidad_dias = $data['cantidad_dias'];    
        $disponible = $data['disponible'];  
        $valor_noche = $data['valor_noche'];  
        $tipo_propiedad_id = $data['tipo_propiedad_id'];
        $no_requeridos = No_requeridos_propiedad ($data);
        $requerimientos = Requerimientos_propiedad($data, $no_requeridos, $conn);
        if ($requerimientos['ok'] == true){
            try{
                $cantidad_habitaciones = $no_requeridos['cantidad_habitaciones'];
                $cantidad_banios = $no_requeridos['cantidad_banios'];
                $cochera = $no_requeridos['cochera'];
                $imagen = $no_requeridos['imagen'];
                $tipo_imagen = $no_requeridos['tipo_imagen'];
                $consulta_repetido = $conn->prepare ("SELECT * FROM propiedades WHERE domicilio = ? and localidad_id = ? and cantidad_huespedes = ? and fecha_inicio_disponibilidad = ? and cantidad_dias = ? and disponible = ? and valor_noche = ? and tipo_propiedad_id = ?");  
                $consulta_repetido->execute([$domicilio, $localidad_id, $cantidad_huespedes, $fecha_inicio_disponibilidad, $cantidad_dias, $disponible, $valor_noche, $tipo_propiedad_id]);
                if ($consulta_repetido->rowCount() > 0){
                    $payload = json_encode ([
                        "status" => "Error",
                        "code" => 400,
                        "data" => "Los campos ya se encuentran en uso"
                    ]);




                    $response-> getBody()->write($payload);
                    return $response->withStatus(400);
                } else{
			   $sql = "SELECT * FROM tipos_propiedad WHERE id = ?";
			   $consulta = $conn->prepare ($sql);
			   $consulta->execute ([$tipo_propiedad_id]);
			   if ( $consulta->rowCount > 0 ){
                    	$sql = "INSERT INTO propiedades (domicilio, localidad_id, cantidad_habitaciones, cantidad_banios, cochera, cantidad_huespedes, fecha_inicio_disponibilidad, cantidad_dias, disponible, valor_noche, tipo_propiedad_id, imagen, tipo_imagen) VALUES (:domicilio, :localidad_id, :cantidad_habitaciones, :cantidad_banios, :cochera, :cantidad_huespedes, :fecha_inicio_disponibilidad, :cantidad_dias, :disponible, :valor_noche, :tipo_propiedad_id, :imagen, :tipo_imagen)";
                    	$consulta = $conn->prepare($sql);
                    	$consulta-> bindValue(":domicilio", $domicilio);
                    	$consulta->bindValue(":localidad_id", $localidad_id);
                    	$consulta->bindValue (":cantidad_habitaciones", $cantidad_habitaciones);
                    	$consulta->bindValue (":cantidad_banios", $cantidad_banios);
                    	$consulta->bindValue (":cochera", $cochera);
                    	$consulta->bindValue (":cantidad_huespedes", $cantidad_huespedes);
                    	$consulta->bindValue(":fecha_inicio_disponibilidad", $fecha_inicio_disponibilidad);
                    	$consulta->bindValue(":cantidad_dias", $cantidad_dias);
                    	$consulta->bindValue(":disponible", $disponible);
                    	$consulta->bindValue("valor_noche", $valor_noche);
                    	$consulta->bindValue("tipo_propiedad_id", $tipo_propiedad_id);
                    	$consulta->bindValue("imagen", $imagen);
                    	$consulta->bindValue("tipo_imagen", $tipo_imagen);
                    	$consulta->execute();
                    	$payload = json_encode([
                        "status" => "success",
                        "code" => 200,
                        "data" => "Propiedad agregada correctamente"
                    	]);
                    	$response-> getBody()->write($payload);
                    	return $response->withStatus(200);
			   } else {
				$payload = json_encode ([
				  "status" => "Error",
				  "code" => "400",
				  "data" => "El tipo de propiedad no existe"
]);
$response->getBody()->write($payload);
return $response->withStatus(400);
			   }
                	}




            } catch (\Exeption $e){
                $payload = json_encode ([
                    "status" => "Error",
                    "code" => 500,
                    "data" => "Error inesperado"
                ]);
                $response-> getBody()->write($payload);
                return $response->withStatus(500);
            }
        } else{
            $string = " ";
            foreach ($requerimientos['vector'] as $aux)
                $string .= $aux . ", ";




                $payload = json_encode ([
                    "status" => "Error",
                    "code" => "400",
                    "data" => $string
                ]);
            $response-> getBody()->write($payload);
            return $response->withStatus(400);
        }
    }
});


//EDITAR


$app-> put ("/propiedades/{id}", function (Request $request, Response $response, $args){
    $data = $request->getParsedBody();
    $resultado = ValidarPropiedad($data);
    if ($resultado['ok'] == false){
        $string = " ";
        foreach ($resultado['vector'] as $aux)
            $string .= $aux . ", ";
        $payload = json_encode ([
            "status" => "Error",
            "code" => "400",
            "data" => $string
        ]);
        $response-> getBody()->write($payload);
        return $response->withStatus(400);
    } else{
        $conn = getConnection ();
        $domicilio = $data['domicilio'];
        $localidad_id = $data['localidad_id'];
        $cantidad_huespedes = $data['cantidad_huespedes'];
        $fecha_inicio_disponibilidad = $data['fecha_inicio_disponibilidad'];
        $cantidad_dias = $data['cantidad_dias'];    
        $disponible = $data['disponible'];  
        $valor_noche = $data['valor_noche'];
        $tipo_propiedad_id = $data['tipo_propiedad_id'];
        $no_requeridos = No_requeridos_propiedad ($data);
        $requerimientos = Requerimientos_propiedad ($data, $no_requeridos, $conn);
        if ($requerimientos['ok']){
            try{
                $cantidad_habitaciones = $no_requeridos['cantidad_habitaciones'];
                $cantidad_banios = $no_requeridos['cantidad_banios'];
                $cochera = $no_requeridos['cochera'];
                $imagen = $no_requeridos['imagen'];
                $tipo_imagen = $no_requeridos['tipo_imagen'];
                $consulta_repetido = $conn->prepare ("SELECT * FROM propiedades WHERE domicilio = ? and localidad_id = ? and cantidad_huespedes = ? and fecha_inicio_disponibilidad = ? and cantidad_dias = ? and disponible = ? and valor_noche = ? and tipo_propiedad_id = ?");
                $consulta_repetido->execute([$domicilio, $localidad_id, $cantidad_huespedes, $fecha_inicio_disponibilidad, $cantidad_dias, $disponible, $valor_noche, $tipo_propiedad_id]);
               
                if ($consulta_repetido->rowCount() > 0){
                    $payload = json_encode ([
                        "status" => "Error",
                        "code" => 400,
                        "data" => "Los campos ya se encuentran en uso"
                    ]);


                    $response-> getBody()->write($payload);
                    return $response->withStatus(400);
                } else{
			   $sql = "SELECT * FROM tipos_propiedad WHERE id = ?";
			   $consulta = $conn->prepare ($sql);
			   $consulta->execute ([$tipo_propiedad_id]);
			   if ( $consulta->rowCount > 0 ){
                    	$sql = "UPDATE propiedades SET domicilio = :domicilio, localidad_id = :localidad_id, cantidad_habitaciones = :cantidad_habitaciones, cantidad_banios = :cantidad_banios, cochera = :cochera, cantidad_huespedes = :cantidad_huespedes, fecha_inicio_disponibilidad = :fecha_inicio_disponibilidad, cantidad_dias = :cantidad_dias, disponible = :disponible, imagen = :imagen, tipo_imagen = :tipo_imagen";
                    	$consulta = $conn->prepare($sql);
                    	$consulta-> bindValue(":domicilio", $domicilio);
                    	$consulta->bindValue(":localidad_id", $localidad_id);
                    	$consulta->bindValue (":cantidad_habitaciones", $cantidad_habitaciones);
                    	$consulta->bindValue (":cantidad_banios", $cantidad_banios);
                    	$consulta->bindValue (":cochera", $cochera);
                    	$consulta->bindValue (":cantidad_huespedes", $cantidad_huespedes);
                    	$consulta->bindValue(":fecha_inicio_disponibilidad", $fecha_inicio_disponibilidad);
                    	$consulta->bindValue(":cantidad_dias", $cantidad_dias);
                    	$consulta->bindValue(":disponible", $disponible);
                    	$consulta->bindValue("imagen", $imagen);
                    	$consulta->bindValue("tipo_imagen", $tipo_imagen);
                    	$consulta->execute();
                    	$payload = json_encode([
                       	"status" => "success",
                        	"code" => 200,
                        	"data" => "Propiedad editada correctamente"
                    	]);
                    	$response-> getBody()->write($payload);
                    	return $response->withStatus(200);
			   }else {
				$payload = json_encode ([
				  "status" => "Error",
				  "code" => "400",
				  "data" => "El tipo de propiedad no existe"
]);
$response->getBody()->write($payload);
return $response->withStatus(400);
			   }
                }
            } catch (\Exeption $e) {
                $payload = json_encode ([
                    "status" => "Error",
                    "code" => "500",
                    "data" => "Error no esperado"
                ]);
                $response-> getBody()->write($payload);
                return $response->withStatus(500);
            }
        } else{
            $string = " ";
            foreach ($requerimientos['vector'] as $aux)
                $string .= $aux . ", ";
            $payload = json_encode ([
                "status" => "Error",
                "code" => 400,
                "data" => $string
            ]);
            $response -> getBody()->write($payload);
            return $response->withStatus(400);
        }
    }
});


//ELIMINAR


function RequerimientosEliminar ($id, $conn){
    $vector = array();
    $ok;
    $consulta = $conn-> prepare ("SELECT * FROM reservas WHERE propiedades_id = ?");
    $consulta->execute([$id]);
    if ($consulta-> rowCount() > 0)
        $vector [] = "El id está siendo utilizado por la tabla reservas";
    if ($vector == null)
        $ok = true;
    else
        $ok = false;
    $resultado = array ('ok' => $ok, 'vector' => $vector);
    return $resultado;
}
$app -> delete("/propiedades/{id}", function (Request $request, Response $response, $args){
    $id = $args['id'];
    try {
        $conn = getConnection ();


        //verificar si el id esta siendo utilizado en la tabla de reservas
        $requerimientos =  RequerimientosEliminar ($id, $conn);
        if ($requerimientos['ok'] == true){
            $sql = "DELETE FROM propiedades WHERE id = ?";
            $consulta = $conn->prepare($sql);
            $consulta->execute([$id]);
            $payload = json_encode ([
                "status" => "success",
                "code" => 200,
                "data" => "La propiedad ha sido eliminada correctamente"
            ]);


            $response-> getBody()->write($payload);
            return $response;
        } else{
            $string = "";
            foreach ($requerimientos['vector'] as $aux)
                $string .= $aux . ", ";
            $payload = json_encode([
                "status" => "Error",
                "code" => 400,
                "data" => $string
            ]);


            $response-> getBody()->write($payload);
            return $response->withStatus(400);
        }
    } catch (PDOException $e){
        $payload = json_encode([
            "status" => "Error",
            "code" => 500,
            "data" => "Error inesperado"
        ]);


        $response-> getBody()->write($payload);
        return $response->withStatus(500);
    }
});
//LISTAR FILTRADO-> select


$app -> get("/propiedades", function (Request $request, Response $response){
    $data = $request->getQueryParams();
    try{
        $conn = getConnection();  //obtiene la conexion de la base de datos
        $sql = "SELECT * FROM propiedades WHERE 1=1";
	  if (!empty($data(['localidad'])
	  	$sql .= "AND localidad_id=". $data['localidad_id'];
	  if (!empty($data(['localidad'])
	  	$sql .= "AND disponible=". $data['disponible'];
	  if (!empty($data(['localidad'])
	  	$sql .= "AND fecha_inicio_disponibilidad=". $data['fecha_inicio_disponibilidad'];
	  if (!empty($data(['localidad'])
	  	$sql .= "AND cantidad_huespedes=". $data['cantidad_huespedes'];
	  $consulta = $conn->prepare ($sql);
	  $consulta->execute ();
	  $tipos = $consulta->fetchAll(PDO::FETCH_ASSOC);
	  $payload = json_encode ([
		"status" => "success",
		"code" => 200,
		"data" => $tipos
  ]);
        $response->getBody()->write($payload);
        return $response->withStatus(200);
    } catch (PDOException $e){
        $payload = json_encode ([
            "status" => "Error",
            "code" => 500,
            "Error" => "Revisa los datos ingresados y vuelva a filtrar"
        ]);


        $response->getBody()->write($payload);
        return $response->withStatus(500);
    }
});


//VER PROPIEDAD
$app -> get ("/propiedades/{id}", function (Request $request, Response $response, $args){
    $id = $args['id'];
    try{
        $conn = getConnection();
        $consulta = $conn->prepare("SELECT * FROM propiedades WHERE id = ?");
        $consulta->execute([$id]);
        $tipos = $consulta->fetchAll(PDO::FETCH_ASSOC);
        if ($tipos != null){
            $payload = json_encode ([
                "status" => "success",
                "code" => "200",
                "data" => $tipos
            ]);


            $response->getBody()->write($payload);
            return $response;
        } else {
            $payload = json_encode ([
                "status" => "Error",
                "code" => "400",
                "data" => "No existe una propiedad con el id proporcionado"
            ]);


            $response -> getBody()->write ($payload);
            return $response;
        }
    } catch (PDOException $e){
        $payload = json_encode ([
            "status" => "Error",
            "code" => 400,
            "data" => "Ha ocurrido un error inesperado"
        ]);


        $response->getBody()->write($payload);
        return $response;
    }
});




                                /*  RESERVAS     */
//CREAR


function ValidarReserva ($data){
    $vector = array();
    $ok;
    if (!empty($data["propiedad_id"]))
        $vector [] = "Debe ingresarse el id";
    if (!empty($data["inquilino_id"]))
        $vector[] = "Debe ingresarse el id ";
    if (!empty($data["fecha_desde"]))
        $vector [] = "Debe ingresarse la fecha";
    if (!empty($data['cantidad_noches']))
        $vector []= "Debe ingresarse la cantidad de noches";
   
    if ($vector == null)
        $ok = true;  
    else
        $ok=false;
    $resultado = array ('ok'=> $ok,'vector'=> $vector);
    return $resultado;    
}


function Requerimientos_reserva ($propiedad_id, $inquilino_id, $fecha_desde, $cantidad_noches){
    $vector = array();
    $ok;
    if (!is_int($propiedad_id))
        $vector [] = "El id de la propiedad debe ser un numero entero";
    if (!is_int($inquilino_id))
        $vector [] = "El id del inquilino debe ser un numero entero";
    if (strtotime($fecha_desde) == false)
        $vector [] = "La fecha de inicio de la reserva fue ingresada incorrectamente";
    if (!is_int($cantidad_noches))
        $vector [] = "Debe ingresar la cantidad de noches en formato numerico entero";
    if ($vector == null)
        $ok = true;
    else
        $ok = false;
    $resultado = array('ok' => $ok, 'vector' => $vector);
    return $resultado;
}


$app-> post("/reservas", function (Request $request, Response $response){
    $data = $request-> getParsedBody();
    $resultado = ValidarReserva ($data);
    if ($resultado['ok'] == false){
        $string = "";
        foreach ($resultado['vector'] as $aux)
            $string .= $aux . ", ";
        $payload  = json_encode ([
                "status" => "Error",
                "code" => 400,
                "data" => $string,
            ]);
        $response-> getBody()->write ($payload);
        return $response->withStatus(400);
    } else{
        $conn = getConnection();
        $propiedad_id = $data['propiedad_id'];
        $inquilino_id = $data['inquilino_id'];
        $fecha_desde = $data['fecha_desde'];
        $cantidad_noches = $data['cantidad_noches'];
        $sql = "SELECT valor_noche FROM propiedades WHERE id = ?";
        $valor_noche = $conn->prepare($sql);  
        $valor_noche->execute([$propiedad_id]);
        $valor_noche = $valor_noche->fetchColumn();
        $valor_total = ($valor_noche * $cantidad_noches);
        $requerimientos = Requerimientos_reserva ($propiedad_id, $inquilino_id, $fecha_desde, $cantidad_noches);
        if ($requerimientos['ok']){
            try{
                $isActivo = $conn->prepare("SELECT activo FROM inquilinos WHERE id = ?");
                $isActivo-> execute([$inquilino_id]);
                $isActivo = $isActivo->fetchColumn();
                $isDisp = $conn-> prepare ("SELECT disponible FROM propiedades WHERE id = ?");
                $isDisp-> execute([$propiedad_id]);
                $isDisp = $isDisp->fetchColumn();
                $fechaDisp = $conn-> prepare ("SELECT fecha_inicio_disponibilidad FROM propiedades WHERE id = ?");
                $fechaDisp-> execute([$propiedad_id]);
                $fechaDisp = $fechaDisp->fetchColumn();
                $fecha_desde_obj = DateTime::createFromFormat('Y-m-d', $fecha_desde);
                $fechaDisp_obj = DateTime::createFromFormat('Y-m-d', $fechaDisp);
                if (($isActivo) and ($isDisp) and ($fecha_desde_obj >= $fechaDisp_obj)){
                    $sql = "INSERT INTO reservas (propiedad_id, inquilino_id, fecha_desde, cantidad_noches, valor_total) VALUES (:propiedad_id, :inquilino_id, :fecha_desde, :cantidad_noches, :valor_total)";
                    $consulta = $conn-> prepare($sql);
                    $consulta->bindValue(":propiedad_id", $propiedad_id);
                    $consulta->bindValue(":inquilino_id", $inquilino_id);
                    $consulta->bindValue(":fecha_desde", $fecha_desde);
                    $consulta->bindValue(":cantidad_noches", $cantidad_noches);
                    $consulta->bindValue(":valor_total", $valor_total);
                    $consulta->execute();


                    $payload  = json_encode ([
                    "status" => "success",
                    "code" => 200,
                    "data" => "Reserva realizada correctamente",
                    ]);
                    $response-> getBody()->write($payload);
                    return $response->withStatus(200);
                } else {
                    $payload = json_encode ([
                        "status" => "Error",
                        "code" => 400,
                        "data" => "El inquilino debe estar activo, la propiedad debe estar disponible y la fecha de reserva debe comenzar dentro del rango de la fecha de disponibilidad de la propiedad"
                    ]);


                    $response->getBody()->write($payload);
                    return $response->withStatus(400);
                }


            } catch (PDOException $e) {
                $payload  = json_encode ([
                "status" => "Error",
                "code" => 500,
                ]);
                $response-> getBody()->write($payload);
                return $response->withStatus(500);
            }
        } else {
            $string = "";
            foreach ($requerimientos['vector'] as $aux)
                $string .= $aux . ", ";
            $payload  = json_encode ([
                "status" => "Error",
                "code" => 400,
                "data" => $string,
            ]);
            $response -> getBody()->write($payload);
            return $response->withStatus(400);
        }
    }
});


$app -> put("/reservas/{id}", function (Request $request, Response $response, $args){
    $id = $args['id'];
    $data = $request-> getParsedBody();
    $conn = getConnection ();
    $fecha_desde = $conn->prepare("SELECT fecha_desde FROM reservas where id = ?");
    $fecha_desde->execute([$id]);
    $fecha_desde = $fecha_desde->fetchColumn();      
    $fecha_actual = date("Y-m-d");
    if ($fecha_actual < $fecha_desde ){
        $resultado = ValidarReserva ($data);
        if ($resultado['ok'] == false){
            $string = "";
            foreach ($resultado ['vector'] as $aux){
                $string .= $aux . ", ";
                }
            $payload = json_encode([
                "status" => "Error",
                "code" => 400,
                "data" => $string
            ]);
            $response-> getBody()->write($payload);
            return $response->withStatus(400);
        }else {
                try {
                    $propiedad_id = $data['propiedad_id'];
                    $inquilino_id = $data['inquilino_id'];
                    $fecha_desde = $data['fecha_desde'];
                    $cantidad_noches = $data['cantidad_noches'];
                    $sql = "UPDATE reservas SET propiedad_id = :propiedad_id, inquilino_id = :inquilino_id, fecha_desde = :fecha_desde, cantidad_noches = :cantidad_noches WHERE reservas_id=: ?";
                    $consulta = $conn->prepare($sql);
                    $consulta-> bindValue(":propiedad_id", $propiedad_id);
                    $consulta->bindValue(":inquilino_id", $inquilino_id);
                    $consulta->bindValue (":fecha_desde", $fecha_desde);
                    $consulta->bindValue(":cantidad_noches", $cantidad_noches);
                    $consulta->execute($id);
                    $payload = json_encode([
                        "status" => "success",
                        "code" => 200,
                        "data" => "reserva editada correctamente"
                    ]);
                    $response-> getBody()->write($payload);
                    return $response->withStatus(200);
                }catch (PDOException $e){
                    $payload = json_encode([
                        "status" => "Error",
                        "code" => 500,
                        "data" => "Error inesperado"
                    ]);
                    $response->getBody()->write($payload);
                    return $response->withStatus(500);
                }
}        
     }else{
                $payload = json_encode([
                    "status" => "Error",
                    "code" => 400,
                    "data" => "La reserva ya comenzo"
                ]);
                $response-> getBody()->write($payload);
                return $response->withStatus(400);
            }
});
//ELIMINAR ANDA
$app -> delete("/reservas/{id}", function (Request $request, Response $response, $args){
    $id = $args['id'];
    $conn = getConnection ();
    $fecha_desde = $conn->prepare("SELECT fecha_desde FROM reservas where reservas_id = ?");
    $fecha_desde->execute([$id]);
    $fecha_desde = $fecha_desde->fetchColumn();
    try {      
        $fecha_actual = date("Y-m-d ");
        if ($fecha_actual < $fecha_desde ){
            $sql = "DELETE FROM reservas WHERE id = ?";
            $consulta = $conn->prepare($sql);
            $consulta->execute([$id]);
            $payload = json_encode ([
                "status" => "success",
                "code" => 200,
                "data" => "La reserva ha sido eliminada correctamente"
            ]);
            $response-> getBody()->write($payload);
            return $response->withStatus(200);
        } else{
            $payload = json_encode([
                "status" => "Error",
                "code" => 400,
                "data" => "La reserva ya comenzo"
            ]);
            $response-> getBody()->write($payload);
            return $response->withStatus(400);
        }
    } catch (PDOException $e){
        $payload = json_encode([
            "status" => "Error",
            "code" => 500,
            "data" => "Error inesperado"
        ]);
        $response-> getBody()->write($payload);
        return $response->withStatus(500);
    }
});
//LISTAR -> select ANDA
$app -> get("/reservas", function (Request $request, Response $response){
    try{
        $connection = getConnection();
        $consulta = $connection->prepare("SELECT * FROM reservas");
        $consulta->execute();
        $tipos = $consulta->fetchAll(PDO::FETCH_ASSOC);


        $payload = json_encode ([
            "status" => "success",
            "code" => "200",
            "data" => $tipos
        ]);




        $response->getBody()->write($payload);
        return $response->withStatus(200);
    } catch (PDOException $e){
        $payload = json_encode ([
            "status" => "error",
            "code" => 400,
        ]);




        $response->getBody()->write($payload);
        return $response->withStatus(400);
    }
});





