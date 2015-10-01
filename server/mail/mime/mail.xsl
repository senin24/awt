<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:php="http://php.net/xsl">

<xsl:template match="mail">
<xsl:text/>MIME-Version: 1.0
From: <xsl:value-of select="php:function('composer_mime_encode', string(@from))"/>
To: <xsl:value-of select="php:function('composer_mime_encode', string(@to))"/>
Message-Id: <xsl:value-of select="concat('&lt;', @message_id, '&gt;')"/>
Date: <xsl:value-of select="@date"/><xsl:text>
</xsl:text>
<xsl:apply-templates select="*" mode="headers"/>
<xsl:call-template name="related"/>
</xsl:template>

<xsl:template name="related">
	<xsl:param name="boundary"><xsl:value-of select="php:function('composer_random')"/></xsl:param>
<xsl:text/>Content-Type: multipart/related; boundary="<xsl:value-of select="$boundary"/>"

This is a multi-part message in MIME format.

--<xsl:value-of select="$boundary"/><xsl:text>
</xsl:text>
<xsl:call-template name="alternative"/>
<xsl:apply-templates select="*" mode="attachments">
	<xsl:with-param name="boundary" select="$boundary"/>
</xsl:apply-templates>
--<xsl:value-of select="$boundary"/>--
</xsl:template>

<xsl:template name="alternative">
	<xsl:param name="boundary"><xsl:value-of select="php:function('composer_random')"/></xsl:param>
<xsl:text/>Content-Type: multipart/alternative; boundary="<xsl:value-of select="$boundary"/>"


--<xsl:value-of select="$boundary"/><xsl:text>
</xsl:text>
<xsl:apply-templates select="*" mode="text"/>
--<xsl:value-of select="$boundary"/><xsl:text>
</xsl:text>
<xsl:apply-templates select="*" mode="html"/>
--<xsl:value-of select="$boundary"/>--
</xsl:template>

</xsl:stylesheet>