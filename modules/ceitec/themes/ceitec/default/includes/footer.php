<?php
if(!empty($this->data['htmlinject']['htmlContentPost'])) {
	foreach($this->data['htmlinject']['htmlContentPost'] AS $c) {
		echo $c;
	}
}
?>
</div><!-- #content -->
</div><!-- #wrap -->

<div id="footer">

    <div style="margin: 0px auto; max-width: 1000px;">

	<div style="float: left;">
		<img src="<?php echo SimpleSAML_Module::getModuleUrl('ceitec/res/img/logo_64.png') ?>">
	</div>
	
	<div style="float: left;">
		<p>CEITEC, Masaryk University, Žerotínovo nám. 9, 601 77 Brno, Czech Republic
			&nbsp; &nbsp; +420 549 497 124 &nbsp;
			<a href="mailto:info@ceitec.cz">info@ceitec.cz</a>
		</p>
		<p>Copyright © CEITEC 2017
		</p>
	</div>
    </div>
	
</div><!-- #footer -->

</body>
</html>

