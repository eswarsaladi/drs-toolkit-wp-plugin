jQuery(document).ready(function($) {
  $(".freewall").each(function(){
    var type = $(this).data("type");
    var cell_height = $(this).data("cell-height");
    var cell_width = $(this).data("cell-width");
    if (type == 'pinterest'){
      var wall = new freewall(this);
      wall.reset({
        selector: ".brick",
        animate: true,
        cellW: cell_width,
        cellH: "auto",
        onResize: function() {
          wall.fitWidth();
        }
      });
      wall.container.find(".brick img").load(function() {
        wall.fitWidth();
      });
    }
    if (type == 'even-row'){
      var w = 1, html = '', i = 0;
      $(this).find(".cell").each( function(){
        var temp = "<div class='cell' style='width:{width}px; height: {height}px; background-image: url("+$(this).data("thumbnail")+")'><div class='info'>"+$(this).children(".info").html()+"</div></div>";
        w = cell_width + cell_height * Math.random() << 0;
        html += temp.replace(/\{height\}/g, cell_height).replace(/\{width\}/g, w);
        i++;
      });
  		$(this).html(html);

  		var wall = new freewall(this);
  		wall.reset({
  			selector: '.cell',
  			animate: true,
  			cellW: 20,
  			cellH: cell_height,
  			onResize: function() {
  				wall.fitWidth();
  			}
  		});
  		wall.fitWidth();
  		// for scroll bar appear;
  		$(window).trigger("resize");
    }
    if (type == 'square'){
      var w = cell_width, h = cell_height, html = '', i = 0;
      $(this).find(".cell").each( function(){
        var temp = "<div class='cell' style='width:{width}px; height: {height}px; background-image: url("+$(this).data("thumbnail")+")'><div class='info'>"+$(this).children(".info").html()+"</div></div>";
        html += temp.replace(/\{height\}/g, h).replace(/\{width\}/g, w);
        i++;
      });
  		$(this).html(html);

  		var wall = new freewall(this);
  		wall.reset({
  			selector: '.cell',
  			animate: true,
  			cellW: cell_width,
  			cellH: cell_height,
  			onResize: function() {
  				wall.refresh();
  			}
  		});
  		wall.fitWidth();
  		// for scroll bar appear;
  		$(window).trigger("resize");
    }
  });
});//end doc ready
