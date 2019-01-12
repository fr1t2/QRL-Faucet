function myCustomValidator(
  $el,      /* jQuery element to validate */
  required, /* is the element required according to the `[required]` attribute */
  parent    /* parent of the jQuery element `$el` */
) {
  if (!required) return true;
  var from = $('#'+$el.attr('data-greater-than')).val(),
      to = $el.val();
  return (parseInt(to) > parseInt(from));
}

// Set default options
Foundation.Abide.defaults.patterns['qrl_address'] = /^(Q|q)[0-9a-zA-Z]{78}$/;
Foundation.Abide.defaults.validators['greater_than'] = myCustomValidator;

$( '#addressForm' ).submit(function( event ) {
  event.preventDefault();
  var form = $( this );

// Post Function for submit button after coinhive
  $.ajax({
    type: 'POST',
    url: '/php/main.php',
    data: form.serialize(),
    dataType: 'json',
    success: function( resp ) {
      console.log( resp );
      success = resp.success;
      if (success) {
        console.log( "Success value is: "+success );
      } else {
        console.log( "Success value is: "+success );   
      }

      console.log( "data Submitted through POST" );
  //alert the user of the goings on here...
      if (success === true) {
        document.getElementById("CoinhiveDiv").innerHTML = 
        "<div class='callout success'>" + 
        "<h1>SUCCESS!</h1>"+
        "<h5>Your Address Has Been Submitted!</h5>"+
        "<p>You will receive a payment in the next few hours...</p>"+
        "</div>";
        }
      else {
       document.getElementById("CoinhiveDiv").innerHTML = 
        "<div class='callout alert'>" + 
        "<h3>Sorry, No Dice For You</h3>" +
        "<p>You've been here recently... </p><p>" + resp.DATETIME + " UTC</p>"+
        "<p>Come back later ;-)</p>" +
        "</div>"; 
      }
      //$("#addressForm")[0].reset();
    }
  });
});

var idleTime = 0;
$(document).ready(function () {
    //Increment the idle time counter every minute.
    var idleInterval = setInterval(timerIncrement, 60000); // 1 minute

    //Zero the idle timer on mouse movement.
    $(this).mousemove(function (e) {
        idleTime = 0;
    });
    $(this).keypress(function (e) {
        idleTime = 0;
    });
});

function timerIncrement() {
    idleTime = idleTime + 1;
    if (idleTime > 9) { // 20 minutes
        window.location.reload();
    }
}

// Initialize Foundation
$(document).foundation();
