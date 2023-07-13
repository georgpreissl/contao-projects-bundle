(function($){
	$(document).ready(function(){
		






window.jsPDF = window.jspdf.jsPDF;

var pdf = new jsPDF( {
   orientation: "landscape",
   unit: "mm",
   format: [297,210]
});


pdf.text($('.project-headline').text(), 20, 30);




/** https://davidwalsh.name/convert-image-data-uri-javascript **/
function getDataUri(url, callback) {
    let image = new Image();
    image.onload = function () {
        callback();
    };
    image.src = url;
}

let url = $('.project-image').attr('src');

getDataUri(url, function(dataUri) {
    pdf.addImage(url,'JPEG', 20, 40, 100, 50,'', 'MEDIUM');
   //  pdf.addPage();                
});


var description = $('.project-description').text();


var $paragraphs = $(".project-description p");
var l = 0;
var lines;
var str;
var space = 3;
var spaceSum = 0;
$paragraphs.each(function(i, current){
   str = $(current).text();
   lines = pdf.setFontSize(9).splitTextToSize(str, 100);

   y = 40 +  l*3.8 + spaceSum;
   pdf.text(lines, 150, y , {
      'lineHeightFactor': 1.2,
      'baseline': 'middle'
   });
   l = l + lines.length;
   spaceSum += space;
   console.log(lines.length);

});





// pdf.addPage();
// pdf.text ("Hallo Universum!", 20, 30);


document.querySelector("#pdfexport").onclick = function () {
    pdf.save ("hallowelt.pdf");
}





	});
})(jQuery);
