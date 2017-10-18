<?php
require_once ('lib/mercadounico.php');

try {
    $mu = new MU('sf-666', 'leandro');

    // Consultar session
    $sessionResponse = $mu->getSession();


    // Crear propiedad
    $propiedad = array(
        "inmobiliaria" => "58bac1d3c2138539ce7f82d0",
        "corredor" => "59baaea3f81aa80293e775aa",
        "dormitorios" => 0,
        "banos" => 0,
        "cochera" => false,
        "operacion" => "58f554cebf61939961ee734c",
        "tipoPropiedad" => "58f556414bba38d0cd9517bb",
        "imagenes" => array(
            "/static/img/houses/1.jpeg",
            "/static/img/houses/2.jpeg"
        ),
        "precio" => array(
            "alquiler" => array(
                "moneda" => "$",
                "valor" => 0,
                "publicado" => false
            ),
            "venta" => array(
                "moneda" => "USD",
                "valor" => 0,
                "publicado" => false
            )
        ),
        "descripcion" => "Probando. Son 2 Terrenos de 10 x 26 cuenta con luz, cloacas y agua corriente.",
        "terreno" => array(
            "ancho" => 0,
            "largo" => 0,
            "superficie" => 0
        ),
        "ubicacion" => array(
            "direccion" => "7 de marzo 3525/3535",
            "coordenadas" => array(
                -60.77,
                -31.68
            ),
            "ciudad" => "58bac0b35a9f803452303226"
        )
    );

    $response = $mu->crearPropriedad($propiedad);
    var_dump($response);

} catch (MUErrorResponseException $e) {
    echo $e->getMessage();
} catch (MUException $e) {
    echo $e->getMessage();
}
