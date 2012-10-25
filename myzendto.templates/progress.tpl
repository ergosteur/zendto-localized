<!DOCTYPE html
        PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
         "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" lang="en-US" xml:lang="en-US">
  <head>
    <link rel="stylesheet" type="text/css" href="css/{#CSSTheme#}.css"/>
    <title>Progress Bar</title>
    <script type="text/javascript" src="js/prototype.js"></script>
  </head>
  <body style="background:#FFF0F0;">

  <script type="text/javascript">
   <!--
   function start(id) { 
    $('progressouter').style.display = 'block';
    // $('#progress').style.visibility = 'visible';
    fire(id);
   }

   var notfirst = 0;
   function fire(id) {
    complete = new Ajax.PeriodicalUpdater('percent',
                                          'get_progress.php?progress_id='+id,
                                          { frequency: 0.25,
                                            decay: 2,
                                            onSuccess: function(transport) { var pc = transport.responseText; $('progressinner').style.width = (100-pc) + '%'; if (notfirst==1) { $('progressouter').style.visibility = 'visible'; $('percent').style.visibility = 'visible'; }; notfirst=1;}});
   }

   -->
  </script>

  <div id="progressouter" style="visibility: hidden; width: 290px; height: 20px; border: 1px solid grey; background-color: white; display: none;">
   <div id="progressinner" style="position: relative; height: 20px; background-color: blue; width: 0%;">
   </div>
  </div>

  <div id="percent" style="visibility:hidden;align:center;background:#FFF0F0;color:#C01010;">
  </div>

  </body>
</html>
