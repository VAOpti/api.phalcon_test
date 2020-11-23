<?php

use Phalcon\Mvc\Micro;
use Phalcon\Loader;
use Phalcon\Di\FactoryDefault;
use Phalcon\Db\Adapter\Pdo\Mysql as PdoMysql;
use Phalcon\Http\Response;

$loader = new Loader();
$loader->registerNamespaces(
    [
        'MyApp\Models' => __DIR__ . '/models/',
    ]
);
$loader->register();

$container = new FactoryDefault();
$container->set(
    'db',
    function () {
        return new PdoMysql(
            [
                'host' => 'localhost',
                'username' => 'root',
                'password' => '',
                'dbname' => 'house_lister',
            ]
        );
    }
);

$app = new Micro($container);

//show error to client
//$app->notFound(function () use ($app) {
//    $app->response->setStatusCode(404, "Not Found")->sendHeaders();
//    echo 'This is crazy, but this page was not found!';
//});

// Retrieves all houses
$app->get(
    '/api.phalcon_test/houses',
    function () use ($app) {
        //TODO: INNER JOIN query returns no results despite the query working in the database. Works fine without INNER JOIN and ON
        $phql = 'SELECT * '
            . 'FROM MyApp\Models\Houses houses '
            . 'INNER JOIN MyApp\Models\Rooms rooms '
            . 'ON houses.house_id = rooms.house_id';

        $houses = $app
            ->modelsManager
            ->executeQuery($phql);

        $data = [];
        foreach ($houses as $house) {
            $data[] = [
                'house_id' => $house->house_id,
                'street' => $house->street,
                //Trying different variables
                'number' => $house->_houses_number,
                'addition' => $house->_houses_addition,
                'zipCode' => $house->_parent_zipCode,
                'city' => $house->_parent_city,
                'rooms' => [
                    "type" => $house->_child_type,
                    "width" => $house->_child_width,
                    "length" => $house->_child_length,
                    "height" => $house->_child_height
                ]
            ];
        }
        return json_encode($data);
    }
);

// Retrieves houses based on primary key
$app->get(
    '/api.phalcon_test/houses/{house_id:[0-9]+}',
    function ($id) use ($app) {
        $phql = 'SELECT * '
            . 'FROM MyApp\Models\Houses '
            . 'WHERE house_id = :house_id:';

        $house = $app
            ->modelsManager
            ->executeQuery(
                $phql,
                [
                    'house_id' => $id,
                ]
            )
            ->getFirst();

        $response = new Response();
        if ($house === false) {
            $response->setJsonContent(
                [
                    'status' => 'NOT-FOUND'
                ]
            );
        } else {
            $response->setJsonContent(
                [
                    'status' => 'FOUND',
                    'data' => [
                        'house_id' => $house->house_id,
                        'street' => $house->street,
                        'number' => $house->number,
                        'addition' => $house->addition,
                        'zipCode' => $house->zipCode,
                        'city' => $house->city
                    ]
                ]
            );
        }
        return $response;
    }
);

// Adds a new house
$app->post(
    '/api.phalcon_test/houses',
    function () use ($app) {
        //TODO: retrieving json array from curl POST goes wrong.
        //POST: curl -i -X POST -d '{"street":"Gelderweg","number":"255","addition":"","zipCode":"5809 AH","city":"Limmen"}' http://localhost/api.phalcon_test/houses/
        //Returns: '{street:Gelderweg,number:255,addition:,zipCode:5809_AH,city:Limmen}'
        //Test: print_r($_POST);

        //This results in getJsonRawBody unable to get the json array
        $house = $app->request->getJsonRawBody(true);

        if ($this->request->isPost()) {
//            $data = json_encode($_POST);
            //print_r($data);
            //    print_r($data);

//            print_r($app->request);
//            $json = file_get_contents($this->request->getPost());
//            print_r(json_decode($json));
        }

//        $house = new \MyApp\Models\Houses();
//        $house->assign(
//            $this->request->getPost(),
//            [
//                'street',
//                'number',
//                'addition',
//                'zipCode',
//                'city'
//            ]
//        );

        $phql = 'INSERT INTO MyApp\Models\Houses '
            . '(street, number, addition, zipCode, city) '
            . 'VALUES '
            . '(:street:, :number:, :addition:, :zipCode:, :city:)';

        $status = $app
            ->modelsManager
            ->executeQuery(
                $phql,
                [
                    'street' => $house->street,
                    'number' => $house->number,
                    'addition' => $house->addition,
                    'zipCode' => $house->zipCode,
                    'city' => $house->city,
                ]
            );

        $response = new Response();

        if ($status->success() === true) {
            $response->setStatusCode(201, 'Created');

            $house->house_id = $status->getModel()->house_id;

            $response->setJsonContent(
                [
                    'status' => 'OK'
                ]
            );
        } else {
            $response->setStatusCode(409, 'Conflict');

            $errors = [];
            foreach ($status->getMessages() as $message) {
                $errors[] = $message->getMessage();
            }

            $response->setJsonContent(
                [
                    'status' => 'ERROR',
                    'messages' => $errors,
                ]
            );
        }

        return $response;
    }
);

// Updates houses based on primary key
$app->put(
    '/api.phalcon_test/houses/{house_id:[0-9]+}',
    function ($house_id) use ($app) {
        $house = $app->request->getJsonRawBody();
        $phql = 'UPDATE MyApp\Models\Houses '
            . 'SET street = :street:, number = :number:, addition = :addition:, zipCode = :zipCode:, city = :city:'
            . 'WHERE house_id = :house_id:';

        $status = $app
            ->modelsManager
            ->executeQuery(
                $phql,
                [
                    'house_id' => $house_id,
                    'street' => $house->street,
                    'number' => $house->number,
                    'addition' => $house->addition,
                    'zipCode' => $house->zipCode,
                    'city' => $house->city,
                ]
            );

        $response = new Response();

        if ($status->success() === true) {
            $response->setJsonContent(
                [
                    'status' => 'OK'
                ]
            );
        } else {
            $response->setStatusCode(409, 'Conflict');

            $errors = [];
            foreach ($status->getMessages() as $message) {
                $errors[] = $message->getMessage();
            }

            $response->setJsonContent(
                [
                    'status' => 'ERROR',
                    'messages' => $errors,
                ]
            );
        }

        return $response;
    }
);

// Deletes houses based on primary key
$app->delete(
    '/api.phalcon_test/houses/{house_id:[0-9]+}',
    function ($house_id) use ($app) {
        $phql = 'DELETE '
            . 'FROM MyApp\Models\Houses '
            . 'WHERE house_id = :house_id:';

        $status = $app
            ->modelsManager
            ->executeQuery(
                $phql,
                [
                    'house_id' => $house_id,
                ]
            );

        $response = new Response();

        if ($status->success() === true) {
            $response->setJsonContent(
                [
                    'status' => 'OK'
                ]
            );
        } else {
            $response->setStatusCode(409, 'Conflict');

            $errors = [];
            foreach ($status->getMessages() as $message) {
                $errors[] = $message->getMessage();
            }

            $response->setJsonContent(
                [
                    'status' => 'ERROR',
                    'messages' => $errors,
                ]
            );
        }

        return $response;
    }
);

$app->handle(
    $_SERVER["REQUEST_URI"]
);
