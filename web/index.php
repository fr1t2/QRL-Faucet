<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="x-ua-compatible" content="ie=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <html class="no-js" lang="en">
  <meta name="description" content="QRL Faucet">
  <meta name="keywords" content="QRL, Faucet,  Free, Crypto Currency, Quantum Resistant Ledger">
  <meta name="author" content="fr1t2">
  <title>QRL Faucet</title>
  <link rel="stylesheet" href="/css/foundation.css">
  <link rel="stylesheet" href="/css/app.css">
  <link rel="shortcut icon" href="/assets/favicon.png" type="image/x-icon" />
</head>
<body>
  <div class="grid-container full ">
    <!-- Small section -->
    <div class="off-canvas position-left hide-for-medium" id="offCanvasLeft" data-off-canvas>
      <!-- Menu -->
      <ul class="vertical menu">
        <li><a href="https://qrl.tips">QRL Faucet</a></li>
        <li><a href="https://explorer.theqrl.org">Explorer</a></li>
        <li><a href="https://wallet.theqrl.org">Wallet</a></li>
        <li><a href="https://docs.theqrl.org">QRL Docs</a></li>
        <li><a href="https://discord.gg/bHPFCPb">Chat</a></li>
      </ul>
      <!-- Close button -->
      <button class="close-button" aria-label="Close menu" type="button" data-close>
        <span aria-hidden="true">&times;</span>
      </button>    
    </div>
    <div class="off-canvas-content" data-off-canvas-content>
      <!-- Page Content -->
        <div class="hero-full-screen" id="top">
          <div class="top-content-section">
            <!-- Large Top Bar -->
            <div class="top-bar show-for-medium">
              <div class="top-bar-left">
                <ul class="menu align-center expanded">
                  <li><a href="https://explorer.theqrl.org" target="_blank">Explorer</a></li>
                  <li><a href="https://wallet.theqrl.org" target="_blank">Wallet</a></li>
                  <li><a href="https://docs.theqrl.org" target="_blank">QRL Docs</a></li>
                  <li><a href="https://discord.gg/bHPFCPb" target="_blank">Chat</a></li>
                  <li><a href="#" data-open="footerModal"> Help</a></li>
                </ul>
              </div>
            </div>
            <div class="mobile-nav-bar title-bar hide-for-medium">
              <div class="title-bar-left">
                <button class="menu-icon hide-for-large" type="button" data-open="offCanvasLeft"></button>
              </div>
              <div class="title-bar-center">
                <span class="title-bar-text"></span>
              </div>
              <div class="title-bar-right">
                <span class="title-bar-text"><a href="#" data-open="footerModal"> Help</a></span>
              </div>
            </div>
          </div>
          <div class="middle-content-section">
            <h1>QRL Faucet</h1>
            <br>
            <br>
            <div class="grid-x grid-padding-x" align="center">
              <div class="small-12 cell" id="a">
                <form data-abide novalidate id="addressForm">
                  <div data-abide-error class="alert callout" style="display: none;">
                    <p><i class="fi-alert"></i> It looks like you have an invalid QRL address there!</p>
                  </div>
                  <div class="grid-container">
                    <div class="grid-x grid-margin-x">
                      <div class="cell small-12">
                        <label>
                          <input type="text" name="address" placeholder="Enter QRL Address" aria-describedby="example1Hint1" aria-errormessage="example1Error1" required pattern="qrl_address" class="animated-search-form-1">
                          <span class="form-error" id="example1Error1">
                            A Valid QRL Address is required.
                          </span>
                        </label>
                      </div>
                    </div>
                  </div>
                  <div class="grid-container">
                    <div class="grid-x grid-margin-x">
                      <fieldset class="cell small-12">

                        <input class="marmalade button" type="submit" value="Submit"/>
                      </fieldset>
                    </div>

                  </div>
                </form>
              </div>
            </div>
          </div>
          <div class="bottom-content-section" data-magellan data-threshold="0">
            <div id="main-content-section" data-magellan-target="main-content-section">
              <div class="small-12 cell">
                <div class="mobile-bottom-bar">
                  <a href="#" class="footer-link">
                    <span class='footer-text'><p>Chain Height</p>
                      <?php
                        $height = shell_exec('../script/getHeight.sh');
                        $Heightjson = json_decode($height);
                        echo "<div class='stat1'>$height</div>"
                      ?>
                    </span>
                  </a>
                  <a href="#" data-open="donationModal"> <img src="/assets/logo.whitesmall.png"></a>
                  <a href="#" class="footer-link">
                    <span class='footer-text'>
                      <p>Faucet Balance</p>
                        <?php
                          $balance = shell_exec('../script/getBalance.sh');
                          $Balancejson = json_decode($balance);
                          echo "<div class='stat1'>QRL $Balancejson</div>"
                        ?>
                    </span>
                  </a>
                </div>
              </div>
            </div>
          </div>
        </div>
        <!-- end Page Content -->
      
      <div class="reveal " id="donationModal" data-reveal>
        <div class="grid-container fluid">
          <div class="grid-x grid-margin-x">
            <div class="small-12 cell">
              <div class="QRLaddress">
                <h2>Donation Adddress</h2>
                  <p class="">
                    <?php
                      $address = shell_exec('../script/listAddresses.sh');
                      $Addressjson = json_decode($address, TRUE);
                      echo $address;
                    ?>
                  </p>  
                </div>
              </div>
            </div>
          </div>
        <button class="close-button" data-close aria-label="Close modal" type="button">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <!-- footer reveal -->
      <div class="small reveal" id="footerModal" data-reveal data-overlay="false">
        <div class="grid-container fluid">
          <div class="grid-x grid-margin-x">
            <div class="cell small-12">
              <h3>QRL.TIPS Faucet </h3>
              <p> This faucet will pay out 0.0000001 Quanta, once a day to any valid QRL address. If you need a QRL address, head over to the <a href="https://cnhv.co/bft2q">QRL Wallet</a> and generate a private key. Enter your wallet Public Key into the form and submit. Payouts are sent out on top of the hour.</p>
            </div>
          </div>
          <div class="grid-x grid-margin-x">
            <div class="cell small-12">
              <p class="lead">Trouble?</p>
              <p>If you are having an issue with the faucet, chances are you have been here within the last 24 hours. This faucet will only pay out once a day to any person.</p>
            </div>
          </div>
        </div>
        <button class="close-button" data-close aria-label="Close modal" type="button">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <script src="/js/vendor/jquery.js"></script>
      <script src="/js/vendor/what-input.js"></script>
      <script src="/js/vendor/foundation.js"></script>
      <script src="/js/app.js"></script>
      </div>
      <!-- end OffCanvas -->  
    </div>
  </body>
</html>