<?php declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

use Metahash\MetaHash;

$crypto = new MetaHash();

$wallets = [
    '0x00fa2a5279f8f0fd2f0f9d3280ad70403f01f9d62f52373833' => 'MetaWat.ch',
    '0x0039f42ad734606d250ea0b0151d4aeab6b4edc6587c4b27ef' => 'KuCoin withdrawal wallet',
    '0x0033626a3977271fd3d1c47e05e3f34c69f38661bdebacad65' => 'KuCoin inner wallet',
    '0x00a335dc550bcb31abf2ec9c9c365ab39413ab6b2dfade258e' => 'Detax.io',
    '0x00a8a58f6cdce810bafc58be25783a0ba6c917dd82d302d404' => 'Bit-Z|BitZ',
    '0x005b891007c2000fee08e085beb91494f1d3753eb8eee354f0' => 'CEX.IO',
    '0x00760dd55ca9a10fcef7ae4f71ef53356ba8f2bd0fa0efe911' => 'Wallet ICO',
];

$balances = $crypto->fetchBalances(\array_keys($wallets));

$results = [];
$fullBalance = 0;
$fullDelegated = 0;

foreach ($balances['result'] as $balanceData) {
    $balance = $balanceData['received'] - $balanceData['spent'];
    $delegated = isset($balanceData['delegate']) ? $balanceData['delegate'] - $balanceData['undelegate'] : 0;

    $results[] = [
        'name'      => $wallets[$balanceData['address']],
        'addr'      => $balanceData['address'],
        'balance'   => $balance / 1e6,
        'delegated' => $delegated / 1e6,
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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="//fonts.googleapis.com/css?family=Raleway:400,300,600" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.min.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/skeleton/2.0.4/skeleton.css"/>
</head>
<body>
<div class="container">
    <div class="row">
        <div class="column" style="margin-top: 25%">
            <h3>Balance: <strong><?php echo $fullBalance / 1e6 ?></strong></h3>
            <h6>❄ Delegated: <strong><?php echo $fullDelegated / 1e6 ?></strong></h6>
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
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
