<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:template match="schedule">
	<xsl:call-template name="menu"/>
</xsl:template>

<xsl:template match="schedule" mode="menu">
	<script src="ui-en/js/jquery.dataTables.min.js" type="text/javascript"></script>
	<link href="ui-en/css/dataTables.bootstrap.min.css" rel="stylesheet" type="text/css"/>
	<script src="ui-en/js/dataTables.bootstrap.min.js" type="text/javascript"></script>
	<script src="ui-en/js/dataTables.responsive.min.js" type="text/javascript"></script>
	<link href="ui-en/css/responsive.bootstrap.min.css" rel="stylesheet" type="text/css"/>
	<script src="ui-en/js/responsive.bootstrap.min.js" type="text/javascript"></script>
	<link href="ui-en/css/bootstrap-datetimepicker.min.css" rel="stylesheet" type="text/css"/>
	<script src="ui-en/js/bootstrap-datetimepicker.min.js" type="text/javascript"></script>
	<div class="container-fluid">
		<div class="row">
			<div class="col-lg-12">
				<h1 class="page-header">Schedule</h1>
				<xsl:apply-templates select="//message"/>
				<xsl:if test="not(job)">
					<div class="alert alert-info alert-dismissable">
						<button type="button" class="close tip-state" data-dismiss="alert" aria-hidden="true" data-tip-state="schedule-create-test">&#215;</button>
						<b>Tip:</b>
						Create a <a href="./?tests=1">test</a> to make schedule available.
					</div>
				</xsl:if>
				<xsl:if test="job">
					<div class="alert alert-info alert-dismissable">
						<button type="button" class="close tip-state" data-dismiss="alert" aria-hidden="true" data-tip-state="set-email">&#215;</button>
						<b>Tip:</b>
						Set email in &quot;<a href="./?settings=1">Settings</a>&quot; to receive regular task reports.
					</div>
				</xsl:if>
			</div>
		</div>
		<div class="row">
			<div class="col-lg-12">
				<div class="panel panel-default">
					<div class="panel-body">
						<table class="table table-striped table-hover table-dataTable"
							data-order='[[4, "asc"]]'>
							<xsl:if test="count(job) &lt;= 10">
								<xsl:attribute name="data-paging">false</xsl:attribute>
							</xsl:if>
							<thead>
								<tr>
									<th>#</th>
									<th>Name</th>
									<th>Test</th>
									<th>Type</th>
									<th>Start time</th>
									<th>Execution period</th>
									<th data-orderable="false"></th>
									<th data-orderable="false"></th>
								</tr>
							</thead>
							<tbody>
								<xsl:for-each select="job">
									<tr>
										<td>
											#<xsl:value-of select="@id"/>
										</td>
										<td>
											<xsl:value-of select="@name"/>
										</td>
										<td>
											<a href="./?test={@test_id}" class="test-id2name">
												<xsl:value-of select="@test_id"/>
											</a>
										</td>
										<td class="task-type">
											<xsl:value-of select="@type"/>
										</td>
										<td class="time-unix2human">
											<xsl:value-of select="@start"/>
										</td>
										<td class="period-unix2human">
											<xsl:value-of select="@period"/>
										</td>
										<td>
											<button type="button" class="btn btn-xs btn-primary"
												data-toggle="modal" data-target="#modal-job-modify-{@id}">
												<i class="fa fa-pencil"></i>
												Modify
											</button>
										</td>
										<td>
											<button type="button" class="btn btn-xs btn-danger"
												data-toggle="modal" data-target="#modal-job-delete-{@id}">
												<i class="glyphicon glyphicon-trash"></i>
												Delete
											</button>
										</td>
									</tr>
								</xsl:for-each>
							</tbody>
						</table>
						<xsl:for-each select="job">
							<div class="modal" id="modal-job-modify-{@id}" role="dialog">
								<div class="modal-dialog modal-lg">
									<div class="panel panel-primary">
										<div class="panel-heading">
											<button type="button" class="close" data-dismiss="modal">&#215;</button>
											Modify:
											<xsl:value-of select="@name"/>
										</div>
										<div class="panel-body">
											<form role="form" method="post" class="form-schedule-job">
												<input type="hidden" name="id" value="{@id}"/>
												<xsl:call-template name="sched_job_form"/>
												<div class="row">
													<div class="col-lg-12">
														<button type="submit" name="modify"
															class="btn btn-block btn-primary">
															<i class="fa fa-pencil"></i>
															Modify
														</button>
													</div>
												</div>
											</form>
										</div>
										<div class="modal-footer">
											<button type="button" class="btn btn-default"
												data-dismiss="modal">
												<i class="fa fa-undo"></i>
												Cancel
											</button>
										</div>
									</div>
								</div>
							</div>
							<div class="modal" id="modal-job-delete-{@id}" role="dialog">
								<div class="modal-dialog modal-sm">
									<div class="panel panel-danger">
										<div class="panel-heading">
											<button type="button" class="close" data-dismiss="modal">&#215;</button>
											Delete:
											<xsl:value-of select="@name"/>
										</div>
										<div class="panel-body">
											<p>
												<b>
													Delete ?
												</b>
											</p>
											<form role="form" method="post">
												<input type="hidden" name="id" value="{@id}"/>
												<button type="submit" name="delete" class="btn btn-block btn-danger">
													<i class="glyphicon glyphicon-trash"></i>
													Delete
												</button>
											</form>
										</div>
										<div class="modal-footer">
											<button type="button" class="btn btn-default"
												data-dismiss="modal">
												<i class="fa fa-undo"></i>
												Cancel
											</button>
										</div>
									</div>
								</div>
							</div>
						</xsl:for-each>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-lg-12" style="overflow: visible /* for date-time picker */;">
				<div class="panel panel-success">
					<div class="panel-body">
						<form role="form" method="post" class="form-schedule-job">
							<xsl:call-template name="sched_job_form">
								<xsl:with-param name="period">3600</xsl:with-param>
							</xsl:call-template>
							<div class="row">
								<div class="col-lg-12">
									<button type="submit" name="add" class="btn btn-block btn-success">
										<i class="fa fa-plus"></i>
										New
									</button>
								</div>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>
</xsl:template>

<xsl:template name="sched_job_form">
	<xsl:param name="name"><xsl:value-of select="@name"></xsl:value-of></xsl:param>
	<xsl:param name="test_id"><xsl:value-of select="@test_id"></xsl:value-of></xsl:param>
	<xsl:param name="type"><xsl:value-of select="@type"></xsl:value-of></xsl:param>
	<xsl:param name="start"><xsl:value-of select="@start"></xsl:value-of></xsl:param>
	<xsl:param name="period"><xsl:value-of select="@period"></xsl:value-of></xsl:param>
	<div class="row">
		<div class="col-lg-2">
			<div class="form-group">
				<label>Name</label>
				<input class="form-control" placeholder="Name" name="name" type="text" value="{$name}"/>
			</div>
		</div>
		<div class="col-lg-2">
			<div class="form-group">
				<label>Test</label>
				<select class="form-control test-id2name" name="test_id" data-selected="{$test_id}"/>
			</div>
		</div>
		<div class="col-lg-2">
			<div class="form-group">
				<label>Type</label>
				<select class="form-control task-type" name="type" data-selected="{$type}"/>
			</div>
		</div>
		<div class="col-lg-4" style="overflow: visible /* for date-time picker */;">
			<div class="form-group">
				<label>Start time</label>
				<div class="input-group date">
					<input class="form-control" placeholder="Start time" name="start" type="text" value="{$start}"/>
					<span class="input-group-addon">
						<span class="glyphicon glyphicon-time"></span>
					</span>
				</div>
			</div>
		</div>
		<div class="col-lg-2">
			<div class="form-group">
				<label>Execution period</label>
				<select class="form-control" name="period">
					<xsl:call-template name="opts_periods">
						<xsl:with-param name="value" select="$period"/>
					</xsl:call-template>
				</select>
			</div>
		</div>
	</div>
</xsl:template>

<xsl:template name="opts_periods">
	<xsl:param name="value">86400</xsl:param>
	<option value="60">
		<xsl:if test="$value = 60">
			<xsl:attribute name="selected"/>
		</xsl:if>
		1 minute
	</option>
	<option value="600">
		<xsl:if test="$value = 600">
			<xsl:attribute name="selected"/>
		</xsl:if>
		10 minutes
	</option>
	<option value="1200">
		<xsl:if test="$value = 1200">
			<xsl:attribute name="selected"/>
		</xsl:if>
		20 minutes
	</option>
	<option value="1800">
		<xsl:if test="$value = 1800">
			<xsl:attribute name="selected"/>
		</xsl:if>
		30 minutes
	</option>
	<option value="3600">
		<xsl:if test="$value = 3600">
			<xsl:attribute name="selected"/>
		</xsl:if>
		1 hour
	</option>
	<option value="7200">
		<xsl:if test="$value = 7200">
			<xsl:attribute name="selected"/>
		</xsl:if>
		2 hours
	</option>
	<option value="14400">
		<xsl:if test="$value = 14400">
			<xsl:attribute name="selected"/>
		</xsl:if>
		4 hours
	</option>
	<option value="43200">
		<xsl:if test="$value = 43200">
			<xsl:attribute name="selected"/>
		</xsl:if>
		12 hours
	</option>
	<option value="86400">
		<xsl:if test="$value = 86400">
			<xsl:attribute name="selected"/>
		</xsl:if>
		1 day
	</option>
	<option value="172800">
		<xsl:if test="$value = 172800">
			<xsl:attribute name="selected"/>
		</xsl:if>
		2 days
	</option>
	<option value="259200">
		<xsl:if test="$value = 259200">
			<xsl:attribute name="selected"/>
		</xsl:if>
		3 days
	</option>
	<option value="604800">
		<xsl:if test="$value = 604800">
			<xsl:attribute name="selected"/>
		</xsl:if>
		1 week
	</option>
</xsl:template>

</xsl:stylesheet>
