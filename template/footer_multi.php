	</div>	
	</div>
	</div>
		<footer>
		<hr>
        <p>Copyright &copy; 2013-2014, Sphinx Technologies Inc.</p>
      </footer>
	
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
	
	
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.18/jquery-ui.min.js"></script>

<script>
function __highlight(s, t) {
  var matcher = new RegExp("("+$.ui.autocomplete.escapeRegex(t)+")", "ig" );
  return s.replace(matcher, "<strong>$1</strong>");
}
$(document).ready(function() {
	$(':checkbox').change(function() {
		
			$("#search_form").trigger('submit');
	
	});
	$(':reset').click(function(){
		location.search ='';
	});
	$('input[name^=reset_]').click(function(){
		$('input[name^='+$(this).attr('data-target')+']').removeAttr('checked');
		$("#search_form").trigger('submit');
			
	});
});
</script>

</body>
</html>