<?php
/**
 * This file is part of ProFTPd Admin
 *
 * @package ProFTPd-Admin
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 *
 * @copyright Lex Brugman <lex_brugman@users.sourceforge.net>
 * @copyright Christian Beer <djangofett@gmx.net>
 * @copyright Ricardo Padilha <ricardo@droboports.com>
 *
 */

include_once ("configs/config.php");
include_once ("includes/AdminClass.php");
global $cfg;

$ac = new AdminClass($cfg);

$field_id      = $cfg['field_id'];
$field_uid      = $cfg['field_uid'];
$field_login    = $cfg['field_login'];
$field_userid  = $cfg['field_userid'];
$field_passwd   = $cfg['field_passwd'];
$field_homedir     = $cfg['field_homedir'];
$field_shell    = $cfg['field_shell'];

$field_login_count    = $cfg['field_login_count'];
$field_last_login     = $cfg['field_last_login'];
$field_create_date     = $cfg['field_create_date'];
$field_bytes_in_used  = $cfg['field_bytes_in_used'];
$field_bytes_out_used = $cfg['field_bytes_out_used'];
$field_files_in_used  = $cfg['field_files_in_used'];
$field_files_out_used = $cfg['field_files_out_used'];

$create_custom_message = $cfg['create_custom_message'];
$update_custom_message = $cfg['update_custom_message'];

$all_users = $ac->get_users();
$users = array();

/* return SFTP name and password */
if (isset($_GET["create_userid"]) && isset($_GET["create_password"])) {
  $infomsg = $create_custom_message;
}
if (isset($_GET["update_userid"]) && isset($_GET["update_password"])) {
  $infomsg = $update_custom_message;
}

/* parse filter  */
$userfilter = array();
$ufilter="";
// see config_example.php on howto activate
if ($cfg['userid_filter_separator'] != "") {
  $ufilter = isset($_REQUEST["uf"]) ? $_REQUEST["uf"] : "";
  foreach ($all_users as $user) {
    $pos = strpos($user[$field_userid], $cfg['userid_filter_separator']);
    // userid's should not start with a - !
    if ($pos != FALSE) {
      $prefix = substr($user[$field_userid], 0, $pos);
      if(@$userfilter[$prefix] == "") {
        $userfilter[$prefix] = $prefix;
      }
    }
  }
}

/* filter users */
if (!empty($all_users)) {
  foreach ($all_users as $user) { 
    if ($ufilter != "") {
      if ($ufilter == "None" && strpos($user[$field_userid], $cfg['userid_filter_separator'])) {
        // filter is None and user has a prefix
        continue;
      }
      if ($ufilter != "None" && strncmp($user[$field_userid], $ufilter, strlen($ufilter)) != 0) {
        // filter is something else and user does not have a prefix
      	continue;
      }
    }
    $users[] = $user;
  }
}

include ("includes/header.php");
?>
<?php include ("includes/messages.php"); ?>

<?php if(!is_array($all_users)) { ?>
<div class="col-sm-12">
  <div class="panel panel-default">
    <div class="panel-heading">
      <h3 class="panel-title">Users</h3>
    </div>
    <div class="panel-body">
      <div class="row">
        <div class="col-sm-12">
          <div class="form-group">
            <p>Currently there are no registered users.</p>
          </div>
          <!-- Actions -->
          <div class="form-group">
            <a class="btn btn-primary pull-right" href="index.php" role="button">Create SFTP &raquo;</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php } else { ?>
<div class="col-sm-12">
  <div class="panel panel-default">
    <div class="panel-heading">
      <h3 class="panel-title">Users</h3>
    </div>
    <div class="panel-body">
      <div class="row">
        <div class="col-sm-12">
          <?php if (count($userfilter) > 0) { ?>
          <!-- Filter toolbar -->
          <div class="form-group">
            <label>Prefix filter:</label>
            <div class="btn-group" role="group">
              <a type="button" class="btn btn-default" href="ftp_list.php">All users</a>
              <a type="button" class="btn btn-default" href="ftp_list.php?uf=None">No prefix</a>
              <div class="btn-group" role="group">
                <button type="button" class="btn btn-default dropdown-toggle" id="idPrefix" data-toggle="dropdown" aria-expanded="false">Prefix <span class="caret"></span></button>
                <ul class="dropdown-menu" role="menu" aria-labelledby="idPrefix">
                <?php foreach ($userfilter as $uf) { ?>
                  <li role="presentation"><a role="menuitem" tabindex="-1" href="ftp_list.php?uf=<?php echo $uf; ?>"><?php echo $uf; ?></a></li>
                <?php } ?>
                </ul>
              </div>
            </div>
          </div>
          <?php } ?>
          <!-- User table -->
          <div class="form-group">
            <table class="table table-striped table-condensed sortable">
              <thead>
                <th class="hidden-xs hidden-sm"><span class="glyphicon glyphicon-tags" aria-hidden="true" title="SFTP Name"></th>
                <th class="hidden-xs hidden-sm hidden-md"><span class="glyphicon glyphicon-time" aria-hidden="true" title="Last login"></th>
                <th class="hidden-xs hidden-sm"><span class="glyphicon glyphicon-list-alt" aria-hidden="true" title="Login count"></th>
                <th class="hidden-xs"><span class="glyphicon glyphicon-signal" aria-hidden="true" title="Uploaded MBs"><span class="glyphicon glyphicon-arrow-up" aria-hidden="true" title="Uploaded MBs"></th>
                <th class="hidden-xs"><span class="glyphicon glyphicon-signal" aria-hidden="true" title="Downloaded MBs"><span class="glyphicon glyphicon-arrow-down" aria-hidden="true" title="Downloaded MBs"></th>
                <th class="hidden-xs"><span class="glyphicon glyphicon-file" aria-hidden="true" title="Uploaded files"><span class="glyphicon glyphicon-arrow-up" aria-hidden="true" title="Uploaded files"></th>
                <th class="hidden-xs"><span class="glyphicon glyphicon-file" aria-hidden="true" title="Downloaded files"><span class="glyphicon glyphicon-arrow-down" aria-hidden="true" title="Downloaded files"></th>
                <th><span class="glyphicon glyphicon-user" aria-hidden="true" title="Login"></th>
                <th class="hidden-xs hidden-sm hidden-md"><span class="glyphicon glyphicon-time" aria-hidden="true" title="Create date"></th>
                <th data-defaultsort="disabled"></th>
              </thead>
              <tbody>
                <?php foreach ($users as $user) { ?>
                  <tr>
                    <td class="pull-middle"><?php echo $user[$field_userid]; ?></a/></td>
                    <td class="pull-middle hidden-xs hidden-sm hidden-md"><?php echo $user[$field_last_login]; ?></td>
                    <td class="pull-middle hidden-xs hidden-sm"><?php echo $user[$field_login_count]; ?></td>
                    <td class="pull-middle hidden-xs"><?php echo sprintf("%2.1f", $user[$field_bytes_in_used] / 1048576); ?></td>
                    <td class="pull-middle hidden-xs"><?php echo sprintf("%2.1f", $user[$field_bytes_out_used] / 1048576); ?></td>
                    <td class="pull-middle hidden-xs"><?php echo $user[$field_files_in_used]; ?></td>
                    <td class="pull-middle hidden-xs"><?php echo $user[$field_files_out_used]; ?></td>
                    <td class="pull-middle"><?php echo $user[$field_login]; ?></td>
                    <td class="pull-middle hidden-xs hidden-sm hidden-md"><?php echo $user[$field_create_date]; ?></td>
                  </tr>
                <?php } ?>
              </tbody>
            </table>
          </div>
          <!-- Actions -->
          <div class="form-group">
            <a class="btn btn-primary pull-right" href="index.php" role="button">Create SFTP &raquo;</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php } ?>

<?php include ("includes/footer.php"); ?>
