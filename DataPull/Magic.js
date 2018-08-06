function PostStuff(stuff, tbl, output) {
  $("#" + stuff).prop("disabled", true);
  $.ajax({
    url: "pull.php",
    type: "post",
    data: JSON.stringify({
      "tblname": tbl
    }),

    success: function(returns) {
      console.log(stuff + "1");
      //document.querySelector("#" + stuff + "1").value = returns;
      $("#" + stuff).prop("disabled", false);


      var text = returns.replace(/[\s]+/g, " ").trim();
      var word = text.split(" ");
      var newHTML = "";

      $.each(word, function(index, value){
        switch(value){
          case "Failed!":
            newHTML += "<span class='fail'>" + value + "&nbsp;</span>\n";
            break;
          case "FAILURE!":
            newHTML += "<span class='fail'>" + value + "&nbsp;</span>";
            break;
          case "Connected!":
            newHTML += "<span class='succc'>" + value + "&nbsp;</span>\n";
            break;
          case "SUCCESS!":
            newHTML += "<span class='succc'>" + value + "&nbsp;</span>";
            break;
          case "FINISH:":
            newHTML += "<span class='succc'>" + value + "&nbsp;</span>";
            break;
          case "DONE!":
            newHTML += "<span class='succc'>" + value + "&nbsp;</span>";
            break;
          case "NEWLN":
            newHTML += "\n";
            break;
          default:
            if (value.substr(0, 4) != "ROWS") {
              newHTML += value + "&nbsp";
            }
            break;
        }
        if (value.substr(0, 5) == "ROWSA") {
          if (value.substr(5) == "0") {
            newHTML += "<span class='warn'>" + value.substr(5) + "&nbsp;</span>";
          } else {
            newHTML += "<span class='succc'>" + value.substr(5) + "&nbsp;</span>";
          }
        } else if (value.substr(0, 5) == "ROWSB") {
          if (value.substr(5) == "0") {
            newHTML += "<span class='succc'>" + value.substr(5) + "&nbsp;</span>";
          } else {
            newHTML += "<span class='warn'>" + value.substr(5) + "&nbsp;</span>";
          }
        }
      });
      $("#" + output).html(newHTML);
    }
  });
}
