<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>HER – Command Line Extractors</title>
    <link rel="stylesheet" href="./templates/bootstrap.css">
  </head>
  <body>

    <div class="container">
    
      <div class="row">
        <div class="col-md-12 col-xs-12">
          <img src="HER-Logo-small.png" class="img-responsive center-block" border="0" />
        </div>
      </div>


      <div class="row">
        <div class="col-md-12 col-xs-12 text-center">
          <h1>Command Line Data Extractors for large datasets</h1>
        </div>
      </div>


      <div class="row">
        <div class="col-md-12 col-xs-12 text-center">
          <p class="lead">Go to the user's folder on the system to access the extraction functionalities.</p>
          <p>Here below you can find the available extracted files. Please remove them after you don't need them any more.</p>
        </div>
      </div>

      <div class="well">
        <div class="row">
          <div class="col-md-12 col-xs-12">

            <?php
                $dirs = glob('files/*' );
                for($i = 0; $i<count($dirs); $i++){
                    ?>
                    <a href="<?php echo(   $dirs[$i]     );  ?>"><h2><?php echo(  str_replace("-", " ",  $dirs[$i] )  );  ?></h2></a>
                    <?php
                }
            ?>
            
          </div>
        </div>
      </div>

    </div>

    <script src="https://code.jquery.com/jquery-3.1.1.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
  </body>
</html>