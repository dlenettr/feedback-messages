<?php
/*
=====================================================
 MWS Feedback Messages v1.2 - by MaRZoCHi
-----------------------------------------------------
 Site: http://dle.net.tr/
-----------------------------------------------------
 Copyright (c) 2015
-----------------------------------------------------
 Lisans: GPL License
=====================================================
*/

if( !defined( 'DATALIFEENGINE' ) OR !defined( 'LOGGED_IN' ) ) {
	die( "Hacking attempt!" );
}

if( $action == "mass_delete" ) {

	if( $_REQUEST['user_hash'] == "" or $_REQUEST['user_hash'] != $dle_login_hash ) {
		die( "Hacking attempt! User not found" );
	}

	if( ! $_POST['selected_feedbacks'] ) {
		msg( "error", "Hata", "Silinecek herhangi bir mesaj seçmediniz.", "{$PHP_SELF}?mod=feedbacks" );
	}
	foreach ( $_POST['selected_feedbacks'] as $f_id ) { $f_id = intval( $f_id ); $db->query( "DELETE FROM " . PREFIX . "_feedbacks WHERE id = '" . intval( $f_id ) . "'" ); }

	msg( "info", "Bilgi", "Seçilen tüm mesajlar başarıyla silindi.", "{$PHP_SELF}?mod=main" );


} else {
	include_once ENGINE_DIR . '/classes/parse.class.php';

	$parse = new ParseFilter( );
	$parse->safe_mode = true;

	$start_from = intval( $_GET['start_from'] );
	if( $start_from < 0 ) $start_from = 0;
	$news_per_page = 50;
	$i = $start_from;

	$gopage = intval( $_GET['gopage'] );
	if( $gopage > 0 ) $start_from = ($gopage - 1) * $news_per_page;

	$result_count = $db->super_query( "SELECT COUNT(*) as count FROM " . PREFIX . "_feedbacks" );

	if ( $result_count['count'] == 0 ) {
		msg( "info", "Uyarı", "Henüz hiç mesaj gönderilmemiş" );
	}

	echoheader( "<i class=\"icon-envelope\"></i>İletişim Mesajları", "Mesajları yönet" );

	$entries = "";

	$db->query( "SELECT * FROM " . PREFIX . "_feedbacks ORDER BY date DESC LIMIT {$start_from},{$news_per_page}" );

	while ( $row = $db->get_array() ) {
		$i++;

		$row['username_to'] = "<a style=\"color: #fff;\" onclick=\"javascript:popupedit('".urlencode( $row['username_to'] )."'); return(false)\" href=\"#\">{$row['username_to']}</a>";
		$row['username_from'] = ( $row['group'] == 5 ) ? $row['username_from'] : "<a style=\"color: #fff;\" onclick=\"javascript:popupedit('".urlencode( $row['username_from'] )."'); return(false)\" href=\"#\">{$row['username_from']}</a>" ;
		$row['ip'] = "<a style=\"color: #fff;\" href=\"?mod=blockip&ip=".urlencode($row['ip'])."\" target=\"_blank\">{$row['ip']}</a>";
		$row['text'] = $parse->decodeBBCodes( $row['text'], false );
		$is_long = ( strlen( $row['text'] ) > 250 ) ? "yes" : "no";
		$row['text'] = "<textarea data-long=\"{$is_long}\" style=\"width:100%; height:80px;font-family:verdana; font-size:11px; border:1px solid #E0E0E0\">" . $row['text'] . "</textarea>";
		$date = langdate( "d.m.Y H:i:s", $row['date'] );

$entries .= <<<HTML
	<li id='table-comm-{$row['id']}' class="arrow-box-left gray">
		<div class="avatar"><input name="selected_comments[]" value="{$row['id']}" type="checkbox"></div>
		<div class="info">
			<span class="name">
				Gönderen: <i class="icon-user"></i> <strong class="label label-blue">{$row['username_from']}</strong>
				Kime: <i class="icon-arrow-right"></i> <strong class="label label-green">{$row['username_to']}</strong>
				Mail: <span class="label label-cyan" style="color: #fff;">{$row['email']}</span>
				IP: <span class="label label-gray">{$row['ip']}</span>
				Konu: <span class="label label-cyan" style="color: #fff;">{$row['subject']}</span>
				Grubu: <span class="label label-red">{$user_group[$row['group']]['group_name']}</span>
			</span>
			<span class="time"><i class="icon-time"></i>{$date}</span>
		</div>
		<div class="content">
			<blockquote>
			{$row['text']}<input type="hidden" name="selected_feedbacks[]" value="{$row['id']}">
			</blockquote>
		</div>
	</li>
HTML;

	}

	$db->free();

	$npp_nav = "<div class=\"news_navigation\" style=\"margin-bottom:5px; margin-top:5px;\">";

	if( $start_from > 0 ) {
		$previous = $start_from - $news_per_page;
		$npp_nav .= "<a href=\"?mod=feedbacks&start_from={$previous}\" title=\"{$lang['edit_prev']}\">&lt;&lt;</a> ";
	}

	if( $result_count['count'] > $news_per_page ) {
		$enpages_count = @ceil( $result_count['count'] / $news_per_page );
		$enpages_start_from = 0;
		$enpages = "";
		if( $enpages_count <= 10 ) {
			for ($j = 1; $j <= $enpages_count; $j ++) {
				if( $enpages_start_from != $start_from ) {
					$enpages .= "<a href=\"?mod=feedbacks&start_from={$enpages_start_from}\">$j</a> ";
				} else {
					$enpages .= "<span>{$j}</span> ";
				}
				$enpages_start_from += $news_per_page;
			}
			$npp_nav .= $enpages;
		} else {
			$start = 1;
			$end = 10;
			if( $start_from > 0 ) {
				if( ($start_from / $news_per_page) > 4 ) {
					$start = @ceil( $start_from / $news_per_page ) - 3;
					$end = $start + 9;
					if( $end > $enpages_count ) {
						$start = $enpages_count - 10;
						$end = $enpages_count - 1;
					}
					$enpages_start_from = ($start - 1) * $news_per_page;
				}
			}

			if( $start > 2 ) {
				$enpages .= "<a href=\"?mod=feedbacks&start_from=0\">1</a> ... ";
			}

			for($j = $start; $j <= $end; $j ++) {
				if( $enpages_start_from != $start_from ) {
					$enpages .= "<a href=\"?mod=feedbacks&start_from={$enpages_start_from}\">$j</a> ";
				} else {
					$enpages .= "<span>{$j}</span> ";
				}
				$enpages_start_from += $news_per_page;
			}
			$enpages_start_from = ($enpages_count - 1) * $news_per_page;
			$enpages .= "... <a href=\"?mod=feedbacks&start_from={$enpages_start_from}\">$enpages_count</a> ";
			$npp_nav .= $enpages;
		}
	}

	if( $result_count['count'] > $i ) {
		$how_next = $result_count['count'] - $i;
		if( $how_next > $news_per_page ) {
			$how_next = $news_per_page;
		}
		$npp_nav .= "<a href=\"?mod=feedbacks&start_from={$i}\" title=\"{$lang['edit_next']}\">&gt;&gt;</a>";
	}
	$npp_nav .= "</div>";


	echo <<<HTML
<script language="javascript" type="text/javascript">
function check_uncheck_all() {
    var frm = document.editmessages;
    for (var i=0;i<frm.elements.length;i++) {
        var elmnt = frm.elements[i];
        if (elmnt.type=='checkbox') {
            if(frm.master_box.checked == true){ elmnt.checked=false; }
            else{ elmnt.checked=true; }
        }
    }
    if(frm.master_box.checked == true){ frm.master_box.checked = false; }
    else{ frm.master_box.checked = true; }
}
function popupedit( name ) {
		var rndval = new Date().getTime();

		$('body').append('<div id="modal-overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: #666666; opacity: .40;filter:Alpha(Opacity=40); z-index: 999; display:none;"></div>');
		$('#modal-overlay').css({'filter' : 'alpha(opacity=40)'}).fadeIn('slow');

		$("#dleuserpopup").remove();
		$("body").append("<div id='dleuserpopup' title='{$lang['user_edhead']}' style='display:none'></div>");

		$('#dleuserpopup').dialog({
			autoOpen: true,
			width: 560,
			height: 500,
			dialogClass: "modalfixed",
			buttons: {
				"{$lang['user_can']}": function() {
					$(this).dialog("close");
					$("#dleuserpopup").remove();
				},
				"{$lang['user_save']}": function() {
					document.getElementById('edituserframe').contentWindow.document.getElementById('saveuserform').submit();
				}
			},
			open: function(event, ui) {
				$("#dleuserpopup").html("<iframe name='edituserframe' id='edituserframe' width='100%' height='400' src='{$PHP_SELF}?mod=editusers&action=edituser&user=" + name + "&rndval=" + rndval + "' frameborder='0' marginwidth='0' marginheight='0' allowtransparency='true'></iframe>");
			},
			beforeClose: function(event, ui) {
				$("#dleuserpopup").html("");
			},
			close: function(event, ui) {
					$('#modal-overlay').fadeOut('slow', function() {
			        $('#modal-overlay').remove();
			    });
			 }
		});

		if ($(window).width() > 830 && $(window).height() > 530 ) {
			$('.modalfixed.ui-dialog').css({position:"fixed"});
			$('#dleuserpopup').dialog( "option", "position", ['0','0'] );
		}
		return false;
}
</script>
<form action="" method="post" name="editmessages">
<div class="box">
	<div class="box-header">
		<div class="title">Gönderilmiş iletişim mesajları</div>
	</div>
	<div class="box-content">
		<div class="row box-section">
			<ul class="chat-box timeline">
			{$entries}
			</ul>
		</div>
		{$npp_nav}
		<div class="row box-section">
			<select class="uniform" name="action"><option value="">---</option><option value="mass_delete">{$lang['edit_seldel']}</option></select>
			<input class="btn btn-gray" type="submit" value="{$lang['b_start']}" />
		</div>
	</div>
</div>
<input type="hidden" name="mod" value="feedbacks">
<input type="hidden" name="user_hash" value="{$dle_login_hash}" />
</form>
HTML;

	echofooter();
}
?>
