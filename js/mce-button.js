(function() {
	tinymce.PluginManager.add('youtube', function( editor, url ) {
		var sh_tag = 'youtube';

		function youtube_parser( yt_url ){
	    var regExp = /^.*((youtu.be\/)|(v\/)|(\/u\/\w\/)|(embed\/)|(watch\?))\??v?=?([^#&?]*).*/;
	    var match = yt_url.match(regExp);
	    return (match&&match[7].length==11)? match[7] : false;
	}

		//helper functions
		function getAttr(s, n) {
			n = new RegExp(n + '=\"([^\"]+)\"', 'g').exec(s);
			return n ?  window.decodeURIComponent(n[1]) : '';
		};

		function html( cls, data , opis ) {
			var placeholder = youtube_parser ( getAttr( data,'link') );
			data = window.encodeURIComponent( data );
			content = window.encodeURIComponent( opis );

			return '<img src="https://img.youtube.com/vi/' + placeholder + '/mqdefault.jpg" class="mceItem ' + cls + '" ' + 'data-sh-attr="' + data + '" data-sh-content="' + opis + '" data-mce-resize="false" data-mce-placeholder="1" />';
		}

		function replaceShortcodes( content ) {
			return content.replace( /\[youtube([^\]]*)\]([^\]]*)\[\/youtube\]/g, function( all, attr, opis ) {
				return html( 'znacznik-youtube', attr , opis );
			});
		}

		function restoreShortcodes( content ) {
			return content.replace( /(?:<p(?: [^>]+)?>)*(<img [^>]+>)(?:<\/p>)*/g, function( match, image ) {
				var data = getAttr( image, 'data-sh-attr' );
				var opis = getAttr( image, 'data-sh-content' );

				if ( data ) {
					return '<p>[' + sh_tag + data + ']' + opis + '[/' + sh_tag + ']</p>';
				}
				return match;
			});
		}

		//add popup
		editor.addCommand('youtube_popup', function(ui, v) {
			//setup defaults
			var link = '';
			if (v.link)
				link = v.link;
			/* var type = 'default';
			if (v.type)
				type = v.type; */
			var content = '';
			if (v.content)
				content = v.content;

			editor.windowManager.open( {
				title: 'Dodawanie filmu z YouTube',
				body: [
					{
						type: 'textbox',
						name: 'link',
						label: 'Link',
						value: link,
						tooltip: 'Leave blank for none'
					},

					/* {
						type: 'listbox',
						name: 'type',
						label: 'Panel Type',
						value: type,
						'values': [
							{text: 'Default', value: 'default'},
							{text: 'Info', value: 'info'},
							{text: 'Primary', value: 'primary'},
							{text: 'Success', value: 'success'},
							{text: 'Warning', value: 'warning'},
							{text: 'Danger', value: 'danger'}
						],
						tooltip: 'Select the type of panel you want'
					}, */
					{
						type: 'textbox',
						name: 'content',
						label: 'Krótki opis',
						value: content,
						multiline: true,
						minWidth: 300,
						minHeight: 100
					}
				],
				onsubmit: function( e ) {
					var shortcode_str = '[' + sh_tag;
					//check for link
					if (typeof e.data.link != 'undefined' && e.data.link.length)
						shortcode_str += ' link="' + e.data.link + '"';
					//check for footer
					//if (typeof e.data.footer != 'undefined' && e.data.footer.length)
					//	shortcode_str += ' footer="' + e.data.footer + '"';

					//add panel content
					shortcode_str += ']' + e.data.content + '[/' + sh_tag + ']';
					//insert shortcode to tinymce
					editor.insertContent( shortcode_str);
				}
			});
	      	});

		//add button
		editor.addButton('youtube', {
			icon: 'youtube',
			tooltip: 'Dodaj film',
			onclick: function() {
				editor.execCommand('youtube_popup','',{
					link : '',
					//type   : 'default',
					content: ''
				});
			}
		});

		//replace from shortcode to an image placeholder
		editor.on('BeforeSetcontent', function(event){
			event.content = replaceShortcodes( event.content );
		});

		//replace from image placeholder to shortcode
		editor.on('GetContent', function(event){
			event.content = restoreShortcodes(event.content);
		});

		//open popup on placeholder double click
		editor.on('DblClick',function(e) {
			var cls  = e.target.className.indexOf('znacznik-youtube');
			if ( e.target.nodeName == 'IMG' && e.target.className.indexOf('znacznik-youtube') > -1 ) {
				var title = e.target.attributes['data-sh-attr'].value;
				title = window.decodeURIComponent(title);
				console.log(title);
				var content = e.target.attributes['data-sh-content'].value;
				editor.execCommand('youtube_popup','',{
					link : getAttr(title,'link'),
					//type   : getAttr(title,'type'),
					content: content
				});
			}
		});
	});
})();
