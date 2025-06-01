<script src="js/jquery/jquery-3.5.1.min.js"></script>
<div class="dashboard_topNav">
	<a href="" id="toggleBtn"><i class="fa fa-navicon"></i></a>
	<div id="topNavContainer">
		<div id="searchResultsContainer">				
			<input type="text" id="searchInput" placeholder="Enter search term...">
			<div id="searchResults">
			</div>
		</div>
		<a href="../index.php" id="logoutBtn"><i class="fa fa-power-off"></i> Log-out</a>
	</div>
</div>

<script>

	// A $( document ).ready() block.
$( document ).ready(function() {
var typingTimer;                //Timer identifier
var doneTypingInterval = 500;  //Time in ms (5 milliseconds interval)
document.addEventListener('keyup', function(ev){
	let el = ev.target;

	// If searchInput is the element
	if(el.id === 'searchInput'){
		let searchTerm = el.value;

		// Use clearTimeout to stop running setTimeout 
		clearTimeout(typingTimer);

		// Set timeout
		typingTimer = setTimeout(function(){
			searchDb(searchTerm);
		}, doneTypingInterval);
	}
});

function searchDb(searchTerm){ 			
let searchResult = document.getElementById('searchResults');
	if(searchTerm.length){ 			
		searchResult.style.display = 'block';
		$.ajax({
			type: 'GET',
			data: {search_term: searchTerm},
			url: 'database/live-search.php',
			success: function(response){
				if(response.length === 0){
					searchResult.innerHTML = 'no data found';
				} else {
					// Loop
					let html = '';
					for (const [tbl, tblRows] of Object.entries(response.data)) {
						tblRows.forEach((row) => {
							let text = '';
							let url = '';
							if(tbl === 'users'){
								text = row.first_name + ' ' + row.last_name;
								url = 'users-view.php';
							}
							if(tbl === 'suppliers') {
								text = row.supplier_name;
								url = 'supplier-view.php';
							}
							if(tbl === 'products') {
								text = row.product_name;
								url = 'product-view.php';
							}

							html += '<a href="'+ url +'">'+ text +'</a> <br/>';
						})
						searchResult.innerHTML = html
				}
				}
			},
			dataType: 'json'
		})
	} else {
		searchResult.style.display = 'none';
	}
}
});
</script>
	