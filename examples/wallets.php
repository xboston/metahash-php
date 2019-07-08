<?php declare(strict_types = 1);

require __DIR__ . '/../vendor/autoload.php';

use Metahash\MetaHash;

$crypto = new MetaHash();

$wallets = [
    '0x00fa2a5279f8f0fd2f0f9d3280ad70403f01f9d62f52373833' => 'MetaWat.ch',
    '0x0039f42ad734606d250ea0b0151d4aeab6b4edc6587c4b27ef' => 'KuCoin',
];

$results = [];
$fullBalance = 0;
$fullDelegated = 0;
foreach ($wallets as $addr => $name) {
    $balanceData = $crypto->fetchBalance($addr);

    $balance = (isset($balanceData['result']) && isset($balanceData['result']['received'])) ? ($balanceData['result']['received'] - $balanceData['result']['spent']) / 1e6 : 0;
    $delegated = (isset($balanceData['result']) && isset($balanceData['result']['delegate'])) ? ($balanceData['result']['delegate'] - $balanceData['result']['undelegate']) / 1e6 : 0;

    $results[] = [
        'addr' => $addr,
        'name' => $name,
        'balance' => $balance,
        'delegated' => $delegated,
    ];

    $fullBalance += $balance;
    $fullDelegated += $delegated;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>#MetaHash - wallet balance</title>
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
        <h3>Balance: <strong><?php echo $fullBalance ?></strong></h3>
        <h6>❄ Delegated: <strong><?php echo $fullDelegated ?></strong></h6>
        <table class="u-full-width">
          <thead>
            <tr>
              <th>Name</th>
              <th>Addr</th>
              <th>Balance</th>
              <th>Delegated</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($results as $result) : ?>
            <tr>
              <td><?php echo $result['name'] ?></td>
              <td><a href="https://metawat.ch/address/<?php echo $result['addr'] ?>" target="_blank"><?php echo $result['addr'] ?></a></td>
              <td><?php echo $result['balance'] ?></td>
              <td><?php echo $result['delegated'] ?></td>
            </tr>
            <?php endforeach?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</body>
</html>
