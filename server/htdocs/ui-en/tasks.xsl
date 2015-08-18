<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:template match="tasks">
	<xsl:call-template name="menu" />
</xsl:template>

<xsl:template match="tasks" mode="menu">
    <link href="css/dataTables.bootstrap.css" rel="stylesheet" />
    <script src="js/jquery.dataTables.min.js"></script>
    <script src="js/dataTables.bootstrap.min.js"></script>
	<div class="container-fluid">
		<div class="row">
			<div class="col-lg-12">
				<h1 class="page-header">Tasks</h1>
			</div>
		</div>
		<xsl:if test="//message">
			<div class="row">
				<div class="col-lg-12">
					<xsl:apply-templates select="//message" />
				</div>
			</div>
		</xsl:if>
		<xsl:if test="task[@status = 'initial']">
			<div class="row">
				<div class="col-lg-12">
					<div class="panel panel-info">
						<div class="panel-heading">
							<i class="fa fa-file-o"></i>
							New
						</div>
						<div class="panel-body">
							<table class="table table-striped table-hover table-dataTable" data-order='[[3, "desc"]]'>
								<xsl:if test="count(task[@status = 'initial']) &lt;= 10">
									<xsl:attribute name="data-paging">false</xsl:attribute>
								</xsl:if>
								<thead>
									<tr>
										<th>Test</th>
										<th>Type</th>
										<th>Debug</th>
										<th>Time</th>
										<th data-orderable="false"></th>
										<th data-orderable="false"></th>
									</tr>
								</thead>
								<tbody>
									<xsl:for-each select="task[@status = 'initial']">
										<tr>
											<td>
												<a href="../?test={@test_id}">
													<xsl:value-of select="@test_name" />
												</a>
											</td>
											<td>
												<xsl:value-of select="@type" />
											</td>
											<td>
												<xsl:if test="@debug">
													<i class="fa fa-check-square"></i>
												</xsl:if>
											</td>
											<td>
												<xsl:value-of select="@time" />
											</td>
											<td>
												<form role="form" method="post">
													<input type="hidden" name="task_id" value="{@id}" />
													<button type="submit" name="cancel" class="btn btn-xs btn-danger">
														<i class="glyphicon glyphicon-trash"></i>
														Cancel
													</button>
												</form>
											</td>
											<td>
												<a href="../?task={@id}">
													<i class="fa fa-list"></i>
													Actions
												</a>
											</td>
										</tr>
									</xsl:for-each>
								</tbody>
							</table>
						</div>
					</div>
				</div>
			</div>
		</xsl:if>
		<xsl:if test="task[@status = 'starting' or @status = 'running']">
			<div class="row">
				<div class="col-lg-12">
					<div class="panel panel-warning">
						<div class="panel-heading">
							<i class="fa fa-play"></i>
							Running
						</div>
						<div class="panel-body">
							<table class="table table-striped table-hover table-dataTable" data-order='[[3, "desc"]]'>
								<xsl:if test="count(task[@status = 'starting' or @status = 'running']) &lt;= 10">
									<xsl:attribute name="data-paging">false</xsl:attribute>
								</xsl:if>
								<thead>
									<tr>
										<th>Test</th>
										<th>Type</th>
										<th>Debug</th>
										<th>Time</th>
										<th data-orderable="false"></th>
									</tr>
								</thead>
								<tbody>
									<xsl:for-each select="task[@status = 'starting' or @status = 'running']">
										<tr>
											<td>
												<a href="../?test={@test_id}">
													<xsl:value-of select="@test_name" />
												</a>
											</td>
											<td>
												<xsl:value-of select="@type" />
											</td>
											<td>
												<xsl:if test="@debug">
													<i class="fa fa-check-square"></i>
												</xsl:if>
											</td>
											<td>
												<xsl:value-of select="@time" />
											</td>
											<td>
												<a href="../?task={@id}">
													<i class="fa fa-list"></i>
													Actions
												</a>
											</td>
										</tr>
									</xsl:for-each>
								</tbody>
							</table>
						</div>
					</div>
				</div>
			</div>
		</xsl:if>
		<xsl:if test="task[@status = 'succeeded' or @status = 'failed']">
			<div class="row">
				<div class="col-lg-12">
					<div class="panel panel-success">
						<div class="panel-heading">
							<i class="fa fa-check-square-o"></i>
							Finished
						</div>
						<div class="panel-body">
							<table class="table table-striped table-hover table-dataTable" data-order='[[3, "desc"]]'>
								<xsl:if test="count(task[@status = 'succeeded' or @status = 'failed']) &lt;= 10">
									<xsl:attribute name="data-paging">false</xsl:attribute>
								</xsl:if>
								<thead>
									<tr>
										<th>Test</th>
										<th>Type</th>
										<th>Debug</th>
										<th>Time</th>
										<th>Status</th>
										<th data-orderable="false"></th>
									</tr>
								</thead>
								<tbody>
									<xsl:for-each select="task[@status = 'succeeded' or @status = 'failed']">
										<tr>
											<xsl:if test="@status = 'succeeded'">
												<xsl:attribute name="class">success</xsl:attribute>
											</xsl:if>
											<xsl:if test="@status = 'failed'">
												<xsl:attribute name="class">danger</xsl:attribute>
											</xsl:if>
											<td>
												<a href="../?test={@test_id}">
													<xsl:value-of select="@test_name" />
												</a>
											</td>
											<td>
												<xsl:value-of select="@type" />
											</td>
											<td>
												<xsl:if test="@debug">
													<i class="fa fa-check-square"></i>
												</xsl:if>
											</td>
											<td>
												<xsl:value-of select="@time" />
											</td>
											<td>
												<xsl:if test="@status = 'succeeded'">
													<i class="fa fa-check text-success"></i>
													<span style="display: none;">1 (order data)</span>
												</xsl:if>
												<xsl:if test="@status = 'failed'">
													<i class="fa fa-times text-failure"></i>
													<span style="display: none;">2 (order data)</span>
												</xsl:if>
											</td>
											<td>
												<a href="../?task={@id}">
													<i class="fa fa-th-list"></i>
													Results
												</a>
											</td>
										</tr>
									</xsl:for-each>
								</tbody>
							</table>
						</div>
					</div>
				</div>
			</div>
		</xsl:if>
		<xsl:if test="task[@status = 'canceled']">
			<div class="row">
				<div class="col-lg-12">
					<div class="panel panel-danger">
						<div class="panel-heading">
							<i class="fa fa-trash-o"></i>
							Canceled
						</div>
						<div class="panel-body">
							<table class="table table-striped table-hover table-dataTable" data-order='[[3, "desc"]]'>
								<xsl:if test="count(task[@status = 'canceled']) &lt;= 10">
									<xsl:attribute name="data-paging">false</xsl:attribute>
								</xsl:if>
								<thead>
									<tr>
										<th>Test</th>
										<th>Type</th>
										<th>Debug</th>
										<th>Time</th>
										<th data-orderable="false"></th>
									</tr>
								</thead>
								<tbody>
									<xsl:for-each select="task[@status = 'canceled']">
										<tr>
											<td>
												<a href="../?test={@test_id}">
													<xsl:value-of select="@test_name" />
												</a>
											</td>
											<td>
												<xsl:value-of select="@type" />
											</td>
											<td>
												<xsl:if test="@debug">
													<i class="fa fa-check-square"></i>
												</xsl:if>
											</td>
											<td>
												<xsl:value-of select="@time" />
											</td>
											<td>
												<a href="../?task={@id}">
													<i class="fa fa-th-list"></i>
													Actions
												</a>
											</td>
										</tr>
									</xsl:for-each>
								</tbody>
							</table>
						</div>
					</div>
				</div>
			</div>
		</xsl:if>
	</div>
</xsl:template>

</xsl:stylesheet>