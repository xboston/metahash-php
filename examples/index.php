<?php declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

use Metahash\Crypto;
use Metahash\Ecdsa;

// !!! very bad
if( isset($_GET) && isset($_GET['address']) ){
  $crypto = new Crypto(new Ecdsa());
  $crypto->net = 'main';
  $balance = json_encode($crypto->fetchBalance($_GET['address']), JSON_PRETTY_PRINT);
}else{
  $balance = false;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>MetaHash php api</title>
  <meta name="description" content="">
  <meta name="author" content="">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="//fonts.googleapis.com/css?family=Raleway:400,300,600" rel="stylesheet" type="text/css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.min.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/skeleton/2.0.4/skeleton.css" />
</head>
<body>
  <div class="container">
    <div class="row">
      <div class="column" style="margin-top: 25%">
        <h4>fetch-balance example</h4>
        <form method="GET">
          <div class="row">
            <div class="columns">
              <label for="exampleEmailInput">Address</label>
              <input class="u-full-width" type="text" placeholder="address" value="0x00fa2a5279f8f0fd2f0f9d3280ad70403f01f9d62f52373833" name="address">
            </div>
          </div>
          <input class="button-primary" type="submit" value="Submit">
          <label for="exampleMessage">Result</label>
          <textarea class="u-full-width" style="min-height:350px" rows="20" cols="20" placeholder="please submit form"><?php echo $balance ?></textarea>
        </form>
      </div>
    </div>
  </div>
</body>
</html>