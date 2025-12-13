import 'package:flutter/material.dart';
import 'package:syncfusion_flutter_charts/charts.dart';

class MainScreen extends StatelessWidget {
  final String userName;
  final Map<String, dynamic> totalsDisplay;
  final Map<String, dynamic> financialHealth;
  final List<Map<String, dynamic>> recentTransactions;
  final Map<String, dynamic> chartData; // includes monthly and category data

  const MainScreen({
    super.key,
    required this.userName,
    required this.totalsDisplay,
    required this.financialHealth,
    required this.recentTransactions,
    required this.chartData,
  });

  @override
  Widget build(BuildContext context) {
    final monthly = chartData['monthly'] ?? {'labels': [], 'income': [], 'expense': []};
    final category = chartData['category'] ?? {'labels': [], 'values': [], 'colors': []};

    return Scaffold(
      appBar: AppBar(
        title: Text('Welcome, $userName! 👋'),
        backgroundColor: Colors.blue,
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              "Here's your financial overview",
              style: TextStyle(color: Colors.grey[600]),
            ),

            const SizedBox(height: 20),

            // Key Metrics - horizontal scrollable row for mobile
            SingleChildScrollView(
              scrollDirection: Axis.horizontal,
              child: Row(
                children: [
                  _metricCard(
                    'Total Balance',
                    totalsDisplay['overall']?['net'] ?? '\$0.00',
                    'Income: ${totalsDisplay['overall']?['income'] ?? '\$0.00'} · Expense: ${totalsDisplay['overall']?['expense'] ?? '\$0.00'}',
                    Colors.blue,
                    Icons.account_balance_wallet,
                  ),
                  const SizedBox(width: 12),
                  _metricCard(
                    'Total Income',
                    totalsDisplay['overall']?['income'] ?? '\$0.00',
                    'Across all transactions',
                    Colors.green,
                    Icons.arrow_upward,
                  ),
                  const SizedBox(width: 12),
                  _metricCard(
                    'Total Expense',
                    totalsDisplay['overall']?['expense'] ?? '\$0.00',
                    'Across all transactions',
                    Colors.red,
                    Icons.arrow_downward,
                  ),
                ],
              ),
            ),

            const SizedBox(height: 20),

            // Financial Health Card
            Card(
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
              elevation: 4,
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  children: [
                    const Text('Financial Health', style: TextStyle(fontWeight: FontWeight.bold)),
                    const SizedBox(height: 12),
                    CircleAvatar(
                      radius: 50,
                      backgroundColor: Colors.grey[200],
                      child: Text(
                        '${financialHealth['score'] ?? 0}/100',
                        style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
                      ),
                    ),
                    const SizedBox(height: 8),
                    Text(
                      financialHealth['grade'] ?? '—',
                      style: TextStyle(
                        fontSize: 24,
                        fontWeight: FontWeight.bold,
                        color: financialHealth['color'] == 'success'
                            ? Colors.green
                            : (financialHealth['color'] == 'danger' ? Colors.red : Colors.orange),
                      ),
                    ),
                  ],
                ),
              ),
            ),

            const SizedBox(height: 20),

            // Income vs Expense Chart
            Card(
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
              elevation: 4,
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  children: [
                    const Text('Income vs Expense', style: TextStyle(fontWeight: FontWeight.bold)),
                    const SizedBox(height: 12),
                    SizedBox(
                      height: 300,
                      child: SfCartesianChart(
                        primaryXAxis: CategoryAxis(),
                        primaryYAxis: NumericAxis(),
                        tooltipBehavior: TooltipBehavior(enable: true),
                        legend: Legend(isVisible: true, position: LegendPosition.top),
                        series: <CartesianSeries>[
                          LineSeries<ChartData, String>(
                            name: 'Income',
                            dataSource: List.generate(monthly['labels']?.length ?? 0,
                                (index) => ChartData(monthly['labels'][index], monthly['income'][index])),
                            xValueMapper: (ChartData data, _) => data.x,
                            yValueMapper: (ChartData data, _) => data.y,
                            color: Colors.green,
                          ),
                          LineSeries<ChartData, String>(
                            name: 'Expense',
                            dataSource: List.generate(monthly['labels']?.length ?? 0,
                                (index) => ChartData(monthly['labels'][index], monthly['expense'][index])),
                            xValueMapper: (ChartData data, _) => data.x,
                            yValueMapper: (ChartData data, _) => data.y,
                            color: Colors.red,
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ),

            const SizedBox(height: 20),

            // Expense Category Pie Chart
            Card(
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
              elevation: 4,
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  children: [
                    const Text('Expense Breakdown', style: TextStyle(fontWeight: FontWeight.bold)),
                    const SizedBox(height: 12),
                    SizedBox(
                      height: 300,
                      child: SfCircularChart(
                        legend: Legend(isVisible: true, overflowMode: LegendItemOverflowMode.wrap),
                        tooltipBehavior: TooltipBehavior(enable: true),
                        series: <CircularSeries>[
                          PieSeries<PieData, String>(
                            dataSource: List.generate(category['labels']?.length ?? 0,
                                (index) => PieData(category['labels'][index], category['values'][index])),
                            xValueMapper: (PieData data, _) => data.x,
                            yValueMapper: (PieData data, _) => data.y,
                            dataLabelSettings: const DataLabelSettings(isVisible: true),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ),

            const SizedBox(height: 20),

            // Recent Transactions Table
            Text('Recent Transactions', style: Theme.of(context).textTheme.titleMedium),
            const SizedBox(height: 8),
            SingleChildScrollView(
              scrollDirection: Axis.horizontal,
              child: DataTable(
                columns: const [
                  DataColumn(label: Text('Type')),
                  DataColumn(label: Text('Description')),
                  DataColumn(label: Text('Category')),
                  DataColumn(label: Text('Amount')),
                  DataColumn(label: Text('Date')),
                ],
                rows: recentTransactions.map((tx) {
                  final isIncome = tx['is_income'] ?? false;
                  return DataRow(cells: [
                    DataCell(Text(tx['type']?.toUpperCase() ?? '', style: TextStyle(color: isIncome ? Colors.green : Colors.red))),
                    DataCell(Text(tx['description'] ?? '—')),
                    DataCell(Text(tx['category_name'] ?? '')),
                    DataCell(Text(tx['display_amount'] ?? '\$0.00', style: TextStyle(color: isIncome ? Colors.green : Colors.red))),
                    DataCell(Text(tx['display_date'] ?? '—')),
                  ]);
                }).toList(),
              ),
            ),
          ],
        ),
      ),
    );
  }

  // Updated _metricCard
  Widget _metricCard(String label, String value, String desc, Color color, IconData icon) {
    return Container(
      width: 180, // fixed width for horizontal scroll
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(20),
        gradient: LinearGradient(
          colors: [color.withOpacity(0.9), color.withOpacity(0.6)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        boxShadow: [
          BoxShadow(
            color: color.withOpacity(0.4),
            blurRadius: 10,
            offset: const Offset(0, 5),
          ),
        ],
      ),
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          CircleAvatar(
            radius: 28,
            backgroundColor: Colors.white.withOpacity(0.2),
            child: Icon(icon, color: Colors.white, size: 28),
          ),
          const SizedBox(height: 12),
          Text(
            label,
            style: const TextStyle(
              fontSize: 14,
              color: Colors.white70,
              fontWeight: FontWeight.w500,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            value,
            style: const TextStyle(
              fontSize: 22,
              color: Colors.white,
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            desc,
            style: const TextStyle(
              fontSize: 11,
              color: Colors.white70,
            ),
          ),
        ],
      ),
    );
  }
}

class ChartData {
  final String x;
  final double y;
  ChartData(this.x, this.y);
}

class PieData {
  final String x;
  final double y;
  PieData(this.x, this.y);
}
