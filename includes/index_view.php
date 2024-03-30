<?php
declare(strict_types=1); //lets you require a specific data type

function display_auctions(){
    if(isset($_SESSION['user_id'])){
        //user is logged in
        ?>

        <?php
    }
    else{
        ?>
        <section id="landing">
            <div><h3>Discover Your Dream Home at Aperture Auctions</h3></div>

        </section>
        <?php
    }
}
