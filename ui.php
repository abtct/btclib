<?php

/** @var IBtcLib $btclib */

use PeopleBitcoins\BtcPhp\BtcLib;
use PeopleBitcoins\BtcPhp\IBtcLib;
use PeopleBitcoins\BtcPhp\TxInfo;
use PeopleBitcoins\BtcPhp\WalletInfo;

const LABEL_WALLETS = "wallets_for_clients";

$FILE_WALLETS = ".wallets.{$btclib->nodeId()}.dat";

/** @var []WalletInfo $wallets */
if(file_exists($FILE_WALLETS)) {
    $wallets = unserialize(file_get_contents($FILE_WALLETS));
} else {
    $wallets = [];
}

$status = [];

$save = false;
$reload = false;

function findwallet($address, &$wallets, &$status)
{
    if(!$address || !is_string($address)) {
        $status['error'] = 'No address';
        return null;
    }

    /** @var ?WalletInfo $w */
    $w = null;

    /** @var WalletInfo $walletInfo */
    foreach($wallets as $walletInfo) {
        if($walletInfo->address == $address) {
            return $walletInfo;
        }
    }

    if($w == null) {
        $status['error'] = 'not our wallet!';
        return null;
    }
}

switch($_POST['act'] ?? '')
{
    case "create-wallet":
        $wallets[] = $btclib->newWallet(LABEL_WALLETS);
        $save = true;
        break;

    case 'info-balance':
        $w = findwallet($_POST['address'] ?? '', $wallets, $status);
        if(!$w) {
            break;
        }

        $status['wallet'] = [
            'name' => $w->rpcwallet,
            'address' => $w->address,
            'balance' => $btclib->getBalance($w),
        ];

        break;

    case 'info-single-transaction':
        $w = findwallet($_POST['address'] ?? '', $wallets, $status);
        if(!$w) {
            break;
        }

        $txid = $_POST['txid'];
        $status['transactions'] = [
            $btclib->getTransactionInfo($w, $txid)
        ];
        break;

    case 'info-transactions':
        $w = findwallet($_POST['address'] ?? '', $wallets, $status);
        if(!$w) {
            break;
        }

        $status['transactions'] = $btclib->getTransactions($w, 50);
        break;

    case 'transfer':
        break;

    default:
        break;
}

if($save) {
    file_put_contents($FILE_WALLETS, serialize($wallets));
    $reload = true;
}

if($reload) {
    header('Location:'.$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING']);
    die("Redirecting..");
}

?>

<html>
<head>
    <title>Test UI</title>
    <link type="text/css" rel="stylesheet" href="ui.css">
</head>
<body>

<?php if($error = $status['error'] ?? ''): ?>
    <h2>Error: <?= $error ?></h2>
<?php endif; ?>

<?php if($message = $status['flash'] ?? ''): ?>
    <h2>Attention: <?= $message ?></h2>
<?php endif; ?>

<div class="section">
    <h3>Wallets</h3>
    <form method="post">
    <p>
        <button type="submit" name="act" value="create-wallet">
            Create wallet
        </button>
    </p>

        <div class="flex-container">
            <?php /** @var WalletInfo $walletInfo */
            foreach($wallets as $i => $w): ?>
                <div class="flex-item" title="click to copy" onclick="addressClick('<?= $w->address ?>');">
                    <?= $w->rpcwallet ?>
                </div>
            <?php endforeach; ?>

        </div>

        <div style="text-align: center; width: 100%;">
            <input placeholder="Click wallet to see address" type="text" readonly id="wallet_address">
            <button type="button" onclick="addressCopyClick()">Copy</button>
        </div>
    </form>
</div>

<hr>

<div class="section">
    <h3>Wallet info</h3>
    <form method="post">
        <div class="section">
            <label for="info_address">Address</label>
            <input type="text" id="info_address" required name="address" value="<?= $status['wallet']['address'] ?? '' ?>">
        </div>

        <h4>Balance</h4>
        <p>Showing balance for wallet <strong><?= $status['wallet']['name'] ?? '' ?></strong>, address <code><?= $status['wallet']['address'] ?? 'X' ?></code></p>

        <p>
            <button type="submit" name="act" value="info-balance">
                Load
            </button>
        </p>
        <p><?= $status['wallet']['balance'] ?? '(balance unknown)' ?></p>

        <h4>Transactions</h4>
        <p>Showing transactions for wallet <strong><?= $status['wallet']['name'] ?? '' ?></strong>, address <code><?= $status['wallet']['address'] ?? 'X' ?></code></p>

        <p>
            <button type="submit" name="act" value="info-transactions">
                Load
            </button>
        </p>

        <table class="tx-table">
            <thead>
            <tr>
                <th width="200px">Tx</th>
                <th>Type</th>
                <th>Time</th>
                <th>Status</th>
                <th>Amount</th>
                <th>Fee</th>
                <th>Confirmations</th>
            </tr>
            </thead>
            <tbody>
            <?php  /** @var TxInfo $tx */
            foreach($status['transactions'] ?? [] as $tx): ?>
                <tr>
                    <td><?= $tx->txid ?></td>
                    <td><?= $tx->displayType() ?></td>
                    <td><?= $tx->displayTime() ?></td>
                    <td><?= $tx->displayStatus() ?></td>
                    <td><?= abs($tx->amount) ?></td>
                    <td><?= $tx->fee ?></td>
                    <td><?= $tx->confirmations ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <div class="section">

            <h3>Single transaction</h3>
            <div class="section">
                <label for="info_txid">Address</label>
                <input type="text" id="info_txid" name="txid" value="<?= $status['transactions'][0]->txid ?? '' ?>">
            </div>
            <p>
                <button type="submit" name="act" value="info-single-transaction">
                    Load
                </button>
            </p>
        </div>

    </form>

</div>

<hr>

<div class="section">
    <h3>Create transaction</h3>
    <form method="post">
        <div class="section">
            <label for="transfer_from">From</label>
            <input type="text" id="transfer_from" required name="from">
        </div>
        <div class="section">
            <label for="transfer_to">To</label>
            <input type="text" id="transfer_to" required name="to">
        </div>
        <div class="section">
            <label for="transfer_amount">Amount</label>
            <input type="number" step="0.0001" id="transfer_amount" required name="amount" value="0.0001">
        </div>
        <p>
            <button type="submit" name="act" value="transfer">
                Send
            </button>
        </p>
        <p>
            <button type="reset">
                Clear
            </button>
        </p>
    </form>
</div>

<script>
    window.Clipboard = (function(window, document, navigator) {
        var textArea,
            copy;

        function isOS() {
            return navigator.userAgent.match(/ipad|iphone/i);
        }

        function createTextArea(text) {
            textArea = document.createElement('textArea');
            textArea.value = text;
            document.body.appendChild(textArea);
        }

        function selectText() {
            var range,
                selection;

            if (isOS()) {
                range = document.createRange();
                range.selectNodeContents(textArea);
                selection = window.getSelection();
                selection.removeAllRanges();
                selection.addRange(range);
                textArea.setSelectionRange(0, 999999);
            } else {
                textArea.select();
            }
        }

        function copyToClipboard() {
            document.execCommand('copy');
            document.body.removeChild(textArea);
        }

        copy = function(text) {
            createTextArea(text);
            selectText();
            copyToClipboard();
        };

        return {
            copy: copy
        };
    })(window, document, navigator);

    function addressClick(address) {
        document.getElementById("wallet_address").value = address
    }

    function addressCopyClick() {
        Clipboard.copy(document.getElementById("wallet_address").value)
    }
</script>

</body>
</html>