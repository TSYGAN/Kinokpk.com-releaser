<?php
/**
 * Polls archive
 * @license GNU GPLv3 http://opensource.org/licenses/gpl-3.0.html
 * @package Kinokpk.com releaser
 * @author ZonD80 <admin@kinokpk.com>
 * @copyright (C) 2008-now, ZonD80, Germany, TorrentsBook.com
 * @link http://dev.kinokpk.com
 */
require_once("include/bittorrent.php");
dbconn();

loggedinorreturn();

$spbegin = "<div style=\"position: static;\" class=\"sp-wrap\"><div class=\"sp-head folded clickable\"><table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\"><tr><td class=\"bottom\" width=\"50%\"><i>���������� ������������</i></td></tr></table></div><div class=\"sp-body\">";
$spend = "</div></div>";

$count = get_row_count("polls");

list($pagertop, $pagerbottom, $limit) = pager(5, $count, array('pollsarchive'));

$pollsrow = sql_query("SELECT id FROM polls ORDER BY id DESC $limit");

$REL_TPL->stdhead("����� �������");

print('<table width="100%" border="1"><tr><td>'.$pagertop.'</td></tr>');
while (list($id) = mysql_fetch_array($pollsrow)) {

	$poll = sql_query("SELECT polls.*, polls_structure.value, polls_structure.id AS sid,polls_votes.vid,polls_votes.user,users.username,users.class FROM polls LEFT JOIN polls_structure ON polls.id = polls_structure.pollid LEFT JOIN polls_votes ON polls_votes.sid=polls_structure.id LEFT JOIN users ON users.id=polls_votes.user WHERE polls.id = $id ORDER BY sid ASC");
	$pquestion = array();
	$pstart = array();
	$pexp = array();
	$public = array();
	$sidvalues = array();
	$votes = array();
	$sids = array();
	$votesres = array();
	$sidcount = array();
	$sidvals = array();
	$votecount = array();
	$usercode = array();
	$comments = array();

	while ($pollarray = mysql_fetch_array($poll)) {
		$pquestion[] = $pollarray['question'];
		$pstart[] = $pollarray['start'];
		$pexp[] = $pollarray['exp'];
		$public[] = $pollarray['public'];
		$comments[] = $pollarray['comments'];
		$sidvalues[$pollarray['sid']] = $pollarray['value'];
		$votes[] = array($pollarray['sid'] => array('vid'=>$pollarray['vid'],'userid'=>$pollarray['user'],'username'=>$pollarray['username'],'userclass'=>$pollarray['class']));
		$sids[] = $pollarray['sid'];
	}

	$pstart = @array_unique($pstart);
	$pstart = $pstart[0];
	if (!$pstart) stderr($REL_LANG->say_by_key('error'), "������ ������ �� ����������");
	$pexp = @array_unique($pexp);
	$pexp = $pexp[0];
	$pquestion = @array_unique($pquestion);
	$pquestion = $pquestion[0];
	$public = @array_unique($public);
	$public = $public[0];
	$comments = @array_unique($comments);
	$comments = $comments[0];

	$sids = @array_unique($sids);
	sort($sids);
	reset($sids);

	print('<tr><td><table width="100%" border="1"><tr><td>����� � '.$id.'</td><td>������: '.mkprettytime($pstart).(!is_null($pexp)?(($pexp > time())?", �������������: ".mkprettytime($pexp):", <font color=\"red\">��������</font>: ".mkprettytime($pexp)):'').'</td></tr><tr><td align="center" class="colhead" colspan="2">'.$pquestion.'</b>'.((get_user_class() >= UC_ADMINISTRATOR)?" [<a href=\"".$REL_SEO->make_link('pollsadmin','action','edit','id',$id)."\">�������������</a>][<a onClick=\"return confirm('�� �������?')\" href=\"".$REL_SEO->make_link('pollsadmin','action','delete','id',$id)."\">�������</a>]":"").'</td></tr>');

	foreach ($sids as $sid)
	$votesres[$sid] = array();

	$voted=0;

	foreach($votes as $votetemp)
	foreach ($votetemp as $sid => $value)
	array_push($votesres[$sid],$value);




	foreach ($votesres as $votedrow => $votes) {

		$sidcount[] = $votedrow;
		$sidvals[] = $sidvalues[$votedrow];
		$votecount[$votedrow] = 0;
		$usercode[$votedrow] = '';

		foreach($votes as $vote) {
			//     print $votedrow."<hr />";
			//   print_r ($vote);
			$vid=$vote['vid'];
			$userid=$vote['userid'];
			$user['username']=$vote['username'];
			$user['class']=$vote['userclass'];

			//      print($vote['vid'].$vote['username'].$vote['userclass'].$vote['userid'].",");
			if ($vote['userid'] == $CURUSER['id']) $voted = $votedrow;
			if (!is_null($vid)) $votecount[$votedrow]++;

			if ((($public) || (get_user_class() >= UC_MODERATOR)) && !is_null($vid))
			$usercode[$votedrow] .= "<a href=\"".$REL_SEO->make_link('userdetails','id',$userid,'username',translit($user['username']))."\">".get_user_class_color($user['class'],$user['username'])."</a>".((get_user_class() >= UC_MODERATOR)?" [<a onClick=\"return confirm('������� ���� �����?')\" href=\"".$REL_SEO->make_link('polloverview','deletevote','vid',$vid)."\">D</a>] ":" ");

			if (($votecount[$votedrow]) >= $maxvotes) $maxvotes = $votecount[$votedrow];

		}
	}     $tvotes = array_sum($votecount);

	@$percentpervote = 50/$maxvotes;
	if (!$percentpervote) $percentpervote=0;

	foreach ($sidcount as $sidkey => $vsid){
		@$percent = round($votecount[$vsid]*100/($tvotes));
		if (!$percent) $percent = 0;
		print("<tr><td width=\"250px\">");
		if ($vsid == $voted)
		print("<b>".$sidvals[$sidkey]." - ��� �����</b>");
		else print($sidvals[$sidkey]);
		print("</td><td><img src=\"./themes/{$REL_CONFIG['ss_uri']}/images/bar_left.gif\"><img src=\"./themes/{$REL_CONFIG['ss_uri']}/images/bar.gif\" height=\"12\" width=\"".round($percentpervote*$votecount[$vsid])."%\"><img src=\"./themes/{$REL_CONFIG['ss_uri']}/images/bar_right.gif\">$percent%, �������: ".$votecount[$vsid]."<br />".((!$usercode[$vsid])?"����� ��������� ��� ����� �� ���������":$spbegin.$usercode[$vsid].$spend)."</td></tr>");
	}
	print('<tr><td>����� ��������� � ������, ����������� ���������</td>');
	print("<td align=\"center\"><h1>����� �������: $tvotes, ������������: $comments</h1><br />[<a href=\"".$REL_SEO->make_link('polloverview','id',$id)."\"><b>���������/������ ������������</b></a>]</td></tr>");

	print ('</table></td></tr>');
}
print('<tr><td>'.$pagerbottom.'</td></tr></table>');
$REL_TPL->stdfoot();

?>
