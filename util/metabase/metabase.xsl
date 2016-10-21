<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<!--
     @(#) $Id: metabase.xsl,v 1.2 2003/08/30 04:02:55 mlemos Exp $
  -->
<xsl:template match="/">
    <html><head><title>Metabase DB Documentation</title>
    <style>
    .titlemini { font-family: Arial, Helvetica, sans-serif; font-size: 15px; font-weight: normal; color: rgb(93, 93, 93); text-decoration: none; letter-spacing: 1px; line-height: 95%; }
    .textmini { font-family: Arial, Helvetica, sans-serif; font-size: 10px; font-weight: normal; color: rgb(93, 93, 93); text-decoration: none; }
    .tablemain { border: solid 0px; margin: solid 0px; }
    .tableheader { padding: 3px; border: solid 0px; margin: 0px; background-color: rgb(235, 235, 235); font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: rgb(133, 133, 133); font-weight: bold; text-decoration: none; }
    .tablebody { padding: 3px; border-top: solid 1px; border-left: solid 1px; border-bottom: solid 1px; border-right: solid 1px; border-color: rgb(210, 210, 210); margin: solid 0px; font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: rgb(133, 133, 133); text-decoration: none; }
    </style>
    </head>
    <body bgcolor="white">
    <br/><span class="titlemini">database
    <xsl:value-of select="/database/name"/></span><br/><br/>
    <table class="tablemain">
    <xsl:for-each select="/database/table">
        <xsl:call-template name="showtable"/>
    </xsl:for-each>
    </table>
    </body></html>
</xsl:template>

<xsl:template name="showtable">
    <tr><td colspan="4" class="tableheader"> <span class="titlemini">table
        <xsl:value-of select="name"/></span> </td></tr>
    <tr><td colspan="4"><span class="textmini">
        <xsl:value-of select="comment"/></span></td></tr>

    <tr><td class="tableheader">field</td>
    <td class="tableheader">type</td>
    <td class="tableheader">comment</td>
    <td class="tableheader">attributes</td>
    </tr>

    <xsl:for-each select="descendant::declaration/field">
        <xsl:call-template name="showfield"/>
    </xsl:for-each>

    <tr><td colspan="2" height="18"></td></tr>
</xsl:template>

<xsl:template name="showfield">

    <tr><td class="tablebody"><span class="textmini">
    <xsl:value-of select="name"/></span></td>

    <td class="tablebody"><span class="textmini">
    <xsl:value-of select="type"/>

    <xsl:if test="length">
      (<xsl:value-of select="length"/>)
    </xsl:if>

    </span></td>

    <td class="tablebody"><span class="textmini">
    <xsl:value-of select="comment"/></span></td>

    <td class="tablebody"><span class="textmini">
    <xsl:for-each select="descendant::default"><xsl:call-template name="showdefault"/>
    </xsl:for-each>

    <xsl:for-each select="descendant::notnull"><xsl:call-template name="shownotnull"/>
    </xsl:for-each>

    <xsl:variable name="curfield" select="current()/name"/>

    <xsl:for-each select="following-sibling::index">
         <xsl:if test="$curfield=field/name">
         <br/>index <xsl:value-of select="name"/>
            <xsl:if test="unique"> unique</xsl:if>
         </xsl:if>

    </xsl:for-each>

    </span></td>

    </tr>
</xsl:template>

<xsl:template name="showdefault">
    default=<xsl:value-of select="//default"/>
</xsl:template>

<xsl:template name="shownotnull">
    notnull=<xsl:value-of select="//notnull"/>
</xsl:template>

</xsl:stylesheet>
