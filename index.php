
<?php
require './header.php';
require_once './includes/index_view.php';
?>
<!--CHANGE COOKIE DOMAIN PARAM BEFORE UPLAOD-->
<!--display ongoing auctions hosted by user-->
<?php
display_auctions();
?>
<section id="hosted-container">

</section>
<?php require './auctions.php'; ?>
<?php require './footer.php'; ?>
</body>
</html>