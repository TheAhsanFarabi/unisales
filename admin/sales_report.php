<?php
try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

// Fetch orders with VAT
$query = "SELECT order_id, time AS order_date,p_amount AS weight, p_price*p_amount AS total_price,
p_price*p_amount*0.1 AS vat, cost*p_amount AS transport_cost, ((p_price*p_amount*0.1)+(cost*p_amount)) as income 
FROM orders JOIN props ON orders.prop_id = props.p_id JOIN transportations ON orders.transport = transportations.truckName";
$result = $pdo->query($query); // execute
$ordersData = $result->fetchAll(PDO::FETCH_ASSOC);

$query1 = "SELECT order_id, time AS order_date, g_amount AS weight, (g_price*g_amount) AS total_price,
(g_amount*g_price*0.1) AS vat, (cost*g_amount) AS transport_cost, ((g_price*g_amount*0.1)+(cost*g_amount)) as income 
FROM orders JOIN gigs ON orders.gig_id = gigs.g_id JOIN transportations ON orders.transport = transportations.truckName";
$result1 = $pdo->query($query1);
$ordersData1 = $result1->fetchAll(PDO::FETCH_ASSOC);


// Close the database connection
$pdo = null;

// Calculate total VAT as income for props
$totalVAT = array_sum(array_column($ordersData, 'vat'));
$totalTransportCost = array_sum(array_column($ordersData, 'transport_cost'));
$totalIncome = $totalVAT + $totalTransportCost;
// calculate total Vat as income for gigs
$totalVAT1 = array_sum(array_column($ordersData1, 'vat'));
$totalTransportCost1 = array_sum(array_column($ordersData1, 'transport_cost'));
$totalIncome1 = $totalVAT1 + $totalTransportCost1;

// Convert data to JSON for JavaScript
$ordersJson = json_encode($ordersData); // for js to show graph1
$ordersJson1 = json_encode($ordersData1); // for js to show graph2


// COUNT 
$total_orders = ($conn->query("SELECT COUNT(*) AS postCount FROM orders"))->fetch_assoc();
$total_gigs = ($conn->query("SELECT COUNT(*) AS postCount FROM gigs"))->fetch_assoc();
$total_props = ($conn->query("SELECT COUNT(*) AS postCount FROM props"))->fetch_assoc();
?>


<h1 class="text-center">Sales Report</h1>

<div class="container row">
    <div class="col p-3 bg-light shadow-lg fs-5 rounded-5 m-2"><i class="fa-solid fa-star text-danger"></i>Total
        Orders<br><b class="display-5 fw-bold"><?= $total_orders['postCount'] ?></b></div>
    <div class="col p-3 bg-light shadow-lg fs-5 rounded-5 m-2"><i class="fa-solid fa-star text-primary"></i>Total
        Gigs<br><b class="display-5 fw-bold"><?= $total_gigs['postCount'] ?></b></div>
    <div class="col p-3 bg-light shadow-lg fs-5 rounded-5 m-2"><i class="fa-solid fa-star text-success"></i>Total
        Props<br><b class="display-5 fw-bold"><?= $total_props['postCount'] ?></b></div>
</div>

<div class="table-responsive">
    <h1>Props Sales Report</h1>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Order Date</th>
                <th>Amount(KG)</th>
                <th>Total Price</th>
                <th>VAT</th>
                <th>Transport Cost</th>
                <th>Income</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($ordersData as $order) { ?>
            <tr>
                <td><?php echo $order['order_id']; ?></td>
                <td><?php echo $order['order_date']; ?></td>
                <td><?php echo $order['weight']; ?> KG</td>
                <td><?php echo $order['total_price']; ?> BDT</td>
                <td><?php echo $order['vat']; ?> BDT</td>
                <td><?php echo $order['transport_cost']; ?> BDT</td>
                <td><?php echo $order['income']; ?> BDT</td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
</div>


<p class="border border-2 p-2 text-center mx-auto rounded-pill fs-3">Total VAT as Income: <b
        class="text-success"><?php echo $totalVAT; ?> BDT</b></p>
<p class="border border-2 p-2 text-center mx-auto rounded-pill fs-3">Total Transport Cost as Income: <b
        class="text-success"><?php echo $totalTransportCost; ?> BDT</b></p>
<p class="border border-2 p-2 text-center mx-auto rounded-pill fs-3">Total Income: <b
        class="text-success"><?php echo $totalIncome; ?> BDT</b></p>

<!-- Include this within your HTML body where you want the chart -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<canvas class="my-4 w-100" id="myChart" width="900" height="380"></canvas>

<script>
// Parse the JSON data generated by PHP
var ordersData = <?php echo $ordersJson; ?>;

// Extract the relevant data for the chart
var orderDates = ordersData.map(item => item.order_date);
var totalIncomes = ordersData.map(item => item.income);
var vats = ordersData.map(item => item.vat);
var transportCosts = ordersData.map(item => item.transport_cost);

// Chart.js code
var ctx = document.getElementById('myChart').getContext('2d');
var myChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: orderDates,
        datasets: [{
                label: 'Total Income',
                data: totalIncomes,
                lineTension: 0,
                backgroundColor: 'transparent',
                borderColor: '#007bff',
                borderWidth: 2,
                pointBackgroundColor: '#007bff'
            },
            {
                label: 'VAT',
                data: vats,
                lineTension: 0,
                backgroundColor: 'transparent',
                borderColor: '#28a745',
                borderWidth: 2,
                pointBackgroundColor: '#28a745'
            },
            {
                label: 'Transport Cost',
                data: transportCosts,
                lineTension: 0,
                backgroundColor: 'transparent',
                borderColor: '#dc3545',
                borderWidth: 2,
                pointBackgroundColor: '#dc3545'
            }
        ]
    },
    options: {
        scales: {
            yAxes: [{
                ticks: {
                    beginAtZero: false
                }
            }]
        },
        legend: {
            display: true,
            position: 'bottom'
        }
    }
});
</script>


<div class="table-responsive">
    <h1>Gigs Sales Report</h1>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Order Date</th>
                <th>Amount(KG)</th>
                <th>Total Price</th>
                <th>VAT</th>
                <th>Transport Cost</th>
                <th>Income</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($ordersData1 as $order) { ?>
            <tr>
                <td><?php echo $order['order_id']; ?></td>
                <td><?php echo $order['order_date']; ?></td>
                <td><?php echo $order['weight']; ?> KG</td>
                <td><?php echo $order['total_price']; ?> BDT</td>
                <td><?php echo $order['vat']; ?> BDT</td>
                <td><?php echo $order['transport_cost']; ?> BDT</td>
                <td><?php echo $order['income']; ?> BDT</td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
</div>


<p class="border border-2 p-2 text-center mx-auto rounded-pill fs-3">Total VAT as Income: <b
        class="text-success"><?php echo $totalVAT1; ?> BDT</b></p>
<p class="border border-2 p-2 text-center mx-auto rounded-pill fs-3">Total Transport Cost as Income: <b
        class="text-success"><?php echo $totalTransportCost1; ?> BDT</b></p>
<p class="border border-2 p-2 text-center mx-auto rounded-pill fs-3">Total Income: <b
        class="text-success"><?php echo $totalIncome1; ?> BDT</b></p>

<!-- Include this within your HTML body where you want the chart -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<canvas class="my-4 w-100" id="myChart1" width="900" height="380"></canvas>

<script>
// Parse the JSON data generated by PHP
var ordersData = <?php echo $ordersJson1; ?>;

// Extract the relevant data for the chart
var orderDates = ordersData.map(item => item.order_date);
var totalIncomes = ordersData.map(item => item.income);
var vats = ordersData.map(item => item.vat);
var transportCosts = ordersData.map(item => item.transport_cost);

// Chart.js code
var ctx = document.getElementById('myChart1').getContext('2d');
var myChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: orderDates,
        datasets: [{
                label: 'Total Income',
                data: totalIncomes,
                lineTension: 0,
                backgroundColor: 'transparent',
                borderColor: '#007bff',
                borderWidth: 2,
                pointBackgroundColor: '#007bff'
            },
            {
                label: 'VAT',
                data: vats,
                lineTension: 0,
                backgroundColor: 'transparent',
                borderColor: '#28a745',
                borderWidth: 2,
                pointBackgroundColor: '#28a745'
            },
            {
                label: 'Transport Cost',
                data: transportCosts,
                lineTension: 0,
                backgroundColor: 'transparent',
                borderColor: '#dc3545',
                borderWidth: 2,
                pointBackgroundColor: '#dc3545'
            }
        ]
    },
    options: {
        scales: {
            yAxes: [{
                ticks: {
                    beginAtZero: false
                }
            }]
        },
        legend: {
            display: true,
            position: 'bottom'
        }
    }
});
</script>