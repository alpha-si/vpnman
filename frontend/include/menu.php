
<nav class="navbar navbar-default navbar-static-top" role="navigation" style="margin-bottom: 0">
	<div class="navbar-header">
		<a class="navbar-brand" href="index.html"><img src='./img/logo_vpnman2.png' style="margin-top:-10px; margin-left:-5px"></a>
	</div>
	<!-- /.navbar-header -->
	<ul class="nav navbar-top-links navbar-right">
		<!-- /.dropdown -->
		<li class="dropdown">
			<a class="dropdown-toggle" data-toggle="dropdown" href="#">
				<i class="fa fa-user fa-fw"></i><span><?php echo(" [" . $_SESSION['sess_username'] . "] "); ?></span><i class="fa fa-caret-down"></i>
			</a>
			<ul class="dropdown-menu dropdown-user">
				<li><a href="javascript:showAbout();"><i class="fa fa-user fa-fw"></i> About</a>
				</li>
				<li class="divider"></li>
				<li><a href="login.php"><i class="fa fa-sign-out fa-fw"></i> Logout</a>
				</li>
			</ul>
		</li>
		
		<!-- /.dropdown -->
	</ul>
	<!-- /.navbar-top-links -->
</nav>
<!-- /.navbar-static-top -->
<nav class="navbar-default navbar-static-side" role="navigation">
	<div class="sidebar-collapse">
		<ul class="nav" id="side-menu">
         <li class="sidebar-search">
             <div class="input-group custom-search-form">
                 <span class="input-group-btn">
                 <select class="form-control" id="select_vpn">
                     <!-- filled by ajax -->
                 </select>
                 </span>
             </div>
             <!-- /input-group -->
         </li>
<?php
         echo "<li><a href=\"index.php\"><i class=\"fa fa-home fa-fw\"></i> Dashboard</a></li>";
         
         if ($_SESSION['sess_role'] == 'ADMIN')
         {
            
            echo "<li><a href=\"users.php\"><i class=\"fa fa-users fa-fw\"></i> Users</a></li>";
         }
         
         if ($_SESSION['sess_role'] != 'USER')
         {
            echo "<li><a href=\"vpn.php\"><i class=\"fa fa-sitemap fa-fw\"></i> VPNs</a></li>";
			}
         
         echo "<li><a href=\"accounts.php\"><i class=\"fa fa-user fa-fw\"></i> Accounts</a></li>";
			
         if ($_SESSION['sess_role'] != 'USER')
         {
            echo "<li><a href=\"networks.php\"><i class=\"fa fa-link fa-fw\"></i> Networks</a></li>";
         }
			
         echo "<li><a href=\"history.php\"><i class=\"fa fa-list fa-fw\"></i> History</a></li>";
         
         if ($_SESSION['sess_role'] == 'ADMIN')
         {
            echo "<li><a href=\"configuration.php\"><i class=\"fa fa-gears fa-fw\"></i> Config</a></li>";
         }
?>
		</ul>
		<!-- /#side-menu -->
	</div>
	<!-- /.sidebar-collapse -->
</nav>
<!-- /.navbar-static-side -->
