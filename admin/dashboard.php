<?php
	// Start the session.
	session_start();

	// Check if user is logged in and is admin
	if(!isset($_SESSION['admin_user']) || $_SESSION['admin_user']['role_as'] != 1 || !isset($_SESSION['admin_user']['is_verified']) || !$_SESSION['admin_user']['is_verified']) {
		// Only unset admin session if it exists
		if(isset($_SESSION['admin_user'])) {
			unset($_SESSION['admin_user']);
		}
		header('location: ../login.php');
		exit();
	}

	// Get user data from session
	$user = $_SESSION['admin_user'];

	// Get redemption status data
	include('database/redemption_status_pie_graph.php');

	// Get monthly sales data
	include('database/monthly_sales.php');

?>
<!DOCTYPE html>
<html>
<head>
	<title>Dashboard - Freshmart</title>
	<link rel="stylesheet" type="text/css" href="css/login.css">
	<script src="https://use.fontawesome.com/0c7a3095b5.js"></script>
	<style>
		.monthly-sales {
			background: white;
			padding: 20px;
			border-radius: 8px;
			box-shadow: 0 2px 4px rgba(0,0,0,0.1);
			margin-top: 30px;
		}
	</style>
</head>
<body>
	<div id="dashboardMainContainer">
		<?php include('partials/app-sidebar.php') ?>
		<div class="dasboard_content_container" id="dasboard_content_container">
			<?php include('partials/app-topnav.php') ?>
			<div class="dashboard_content">
				<div class="dashboard_content_main">
					<div class="col50">
						<figure class="highcharts-figure">
						    <div id="redemptionContainer"></div>
						    <p class="highcharts-description">
						        Here is the breakdown of in-store redemptions by status.
						    </p>
						</figure>						
					</div>			
					<div class="col50">
						<figure class="highcharts-figure">
						    <div id="topCustomersContainer"></div>
						    <p class="highcharts-description">
						        Here are the top 5 customers by monthly spending.
						    </p>
						</figure>						
					</div>			
				</div>
				<div class="monthly-sales">
					<figure class="highcharts-figure">
						<div id="monthlySalesContainer"></div>
						<p class="highcharts-description">
							Monthly sales trend for the past 12 months.
						</p>
					</figure>
				</div>
			</div>
		</div>
	</div>

<script src="js/script.js"></script>
<script src="https://code.highcharts.com/highcharts.js"></script>
<script src="https://code.highcharts.com/modules/exporting.js"></script>
<script src="https://code.highcharts.com/modules/export-data.js"></script>
<script src="https://code.highcharts.com/modules/accessibility.js"></script>

<?php
// Get top 5 customers by monthly spending
$top_customers_query = "SELECT 
    cd.first_name,
    cd.last_name,
    cd.monthly_spending,
    lt.tier_name
FROM customer_details cd
LEFT JOIN loyalty_tiers lt ON cd.loyalty_tier = lt.id
ORDER BY cd.monthly_spending DESC
LIMIT 5";

$result = $con->query($top_customers_query);
$top_customers_data = [];
$customer_categories = [];

while ($row = $result->fetch_assoc()) {
    $customer_categories[] = $row['first_name'] . ' ' . $row['last_name'];
    $top_customers_data[] = [
        'y' => floatval($row['monthly_spending']),
        'tier' => $row['tier_name']
    ];
}
?>

<script>
	// Redemption Status Pie Chart
	var redemptionData = <?= json_encode($results) ?>;
	Highcharts.chart('redemptionContainer', {
	    chart: {
	        plotBackgroundColor: null,
	        plotBorderWidth: null,
	        plotShadow: false,
	        type: 'pie'
	    },
	    title: {
	        text: 'In-Store Redemptions By Status',
	        align: 'left'
	    },
	    tooltip: {
	        pointFormatter: function(){
	            var point = this;
	            return `<b>${point.name}</b>: ${point.y} redemptions`
	        }
	    },
	    plotOptions: {
	        pie: {
	            allowPointSelect: true,
	            cursor: 'pointer',
	            dataLabels: {
	                enabled: true,
	                format: '<b>{point.name}</b>: {point.y}'
	            }
	        }
	    },
	    series: [{
	        name: 'Status',
	        colorByPoint: true,
	        data: redemptionData
	    }]
	});

	// Top Customers Bar Chart
	Highcharts.chart('topCustomersContainer', {
	    chart: {
	        type: 'column'
	    },
	    title: {
	        text: 'Top 5 Customers by Monthly Spending',
	        align: 'left'
	    },
	    xAxis: {
	        categories: <?= json_encode($customer_categories) ?>,
	        crosshair: true
	    },
	    yAxis: {
	        min: 0,
	        title: {
	            text: 'Monthly Spending ($)'
	        },
	        labels: {
	            formatter: function() {
	                return '$' + this.value.toLocaleString();
	            }
	        }
	    },
	    tooltip: {
	        formatter: function() {
	            return '<b>' + this.x + '</b><br/>' +
	                   'Spending: $' + this.y.toLocaleString() + '<br/>' +
	                   'Tier: ' + this.point.tier;
	        }
	    },
	    plotOptions: {
	        column: {
	            pointPadding: 0.2,
	            borderWidth: 0,
	            colorByPoint: true
	        }
	    },
	    colors: [
	        '#4e73df',
	        '#36b9cc',
	        '#1cc88a',
	        '#f6c23e',
	        '#e74a3b'
	    ],
	    series: [{
	        name: 'Monthly Spending',
	        data: <?= json_encode($top_customers_data) ?>,
	        showInLegend: false
	    }]
	});

	// Monthly Sales Line Chart
	Highcharts.chart('monthlySalesContainer', {
	  chart: {
	  	type: 'spline'
	  },
	  title: {
	        text: 'Monthly Sales Trend',
	    align: 'left'
	  },
	    xAxis: {
	        categories: <?= json_encode($monthly_categories) ?>,
	        accessibility: {
	            description: 'Months of the year'
	        }
	    },
	  yAxis: {
	    title: {
	            text: 'Total Sales ($)'
	        },
	        labels: {
	            formatter: function() {
	                return '$' + this.value.toLocaleString();
	            }
	        }
	    },
	    tooltip: {
	        formatter: function() {
	            return '<b>' + this.x + '</b><br/>' +
	                   'Total Sales: $' + this.y.toLocaleString();
	        }
	    },
	    plotOptions: {
	        spline: {
	            marker: {
	                radius: 4,
	                lineColor: '#666666',
	                lineWidth: 1
	            }
	    }
	  },
	  series: [{
	        name: 'Total Sales',
	        data: <?= json_encode($monthly_data) ?>,
	        color: '#4CAF50'
	    }]
	});
</script>

</body>
</html>