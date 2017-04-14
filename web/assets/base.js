$(document).ready(function() {

	var w = screen.width;

	var $qrcode = $('div#qrcode');
	$qrcode.qrcode({

		text: $qrcode.data('qrcode'),
			// render method: 'canvas', 'image' or 'div'
		render: 'canvas',

			// version range somewhere in 1 .. 40
		minVersion: 1,
		maxVersion: 40,

			// error correction level: 'L', 'M', 'Q' or 'H'
		ecLevel: 'H',
			// offset in pixel if drawn onto existing canvas
		left: 0,
		top: 0,
			// size in pixel
		size: w < 320 ? 200 : 300,
			// code color or image element
		fill: '#000',
			// background color or image element, null for transparent background
		background: null,
			// corner radius relative to module width: 0.0 .. 0.5
		radius: 0.2,
			// quiet zone in modules
		quiet: 3,
		mode: 0,
		mSize: 0.1,
		mPosX: 0.5,
		mPosY: 0.5,
		label: 'no label',
		fontname: 'sans',
		fontcolor: '#000',

		image: null
	});

    var $projects = $('div#projects');
    var $project_model = $('div#project_model');
    var projects = $projects.data('projects');
    var s3_img = $projects.data('s3_img');

    if ($.isEmptyObject(projects)){

		$('div#no_projects').show();

    } else {

		var projects_ids = $.map(projects.data, function(obj, index) {
			return index;
		});

		var projects_key = 'cwvd_projects_' + project_ids.join('_');

		var p_order = localStorage.getItem(projects_key);

		if (p_order){

			var p_ary = p_order.split('_');

		} else {

			p_ary = shuffle_ary(projects_ids);

			localStorage.setItem(projects_key, p_ary.join('_'));

		}

		$.each(p_ary, function(index, value){

			var data = projects.data[value];

			var $el = $project_model.clone();

			if (data.img){
				var $img = $el.find('img').eq(0);
				$img.attr('src', s3_img + data.img);
			}

			var $caption = $el.find('div.caption').eq(0);

			if (data.title){
				var $title = $caption.find('h3').eq(0);
				$title.text(data.title);
			}

			if (data.text){
				var $text = $caption.find('p').eq(0);
				$text.text(data.title);
			}



			$el = $project_model.before($el);
			$el.show();
		});

	}


	$el = $project_model.clone();

	$el = $project_model.before($el);
	$el.show();



	function shuffle_ary(ary) {

		for (var i = ary.length - 1; i > 0; i--) {

			var j = Math.floor(Math.random() * (i + 1));
			var temp = array[i];
			ary[i] = ary[j];
			ary[j] = temp;

		}

		return ary;
	}
});