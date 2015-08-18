<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:template match="register">
	<div class="container">
		<div class="row">
			<div class="col-md-4 col-md-offset-4">
				<div class="panel panel-default panel-login">
					<div class="panel-heading">
						<h3 class="panel-title">Sign Up</h3>
					</div>
					<div class="panel-body">
						<xsl:apply-templates select="//message" />
						<form role="form" method="post">
							<fieldset>
								<div class="form-group">
									<input class="form-control" placeholder="Login" name="user"
										type="text" autofocus="1" />
								</div>
								<div class="form-group">
									<input class="form-control" placeholder="Password" name="password1"
										type="password" value="" />
								</div>
								<div class="form-group">
									<input class="form-control" placeholder="Password (confirm)"
										name="password2" type="password" value="" />
								</div>
								<input type="submit" name="register" value="Register"
									class="btn btn-lg btn-success btn-block" />
								<div class="form-group">
									<a href="../">Sign In</a>
								</div>
							</fieldset>
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>
</xsl:template>
</xsl:stylesheet>