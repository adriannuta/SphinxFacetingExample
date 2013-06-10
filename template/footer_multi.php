	</div>	<hr>
		<footer>
		
        <p>Copyright &copy; 2001-2013, Sphinx Technologies Inc.</p>
      </footer>
	</div>
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
});
</script>

</body>
</html>