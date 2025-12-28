import 'package:flutter/material.dart';
import 'package:syncfusion_flutter_charts/charts.dart';

class MyChart extends StatelessWidget {
  final Map<String, dynamic>? monthly;
  final Map<String, dynamic>? category;

  const MyChart({super.key, this.monthly, this.category});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    final TooltipBehavior cartesianTooltip = TooltipBehavior(
      enable: true,
      canShowMarker: true,
      header: '',
      format: 'point.x : point.y',
    );

    final TooltipBehavior pieTooltip = TooltipBehavior(
      enable: true,
      header: '',
      format: 'point.x : point.y',
    );

    // Parse monthly income/expense chartData from dashboard
    final List<String> monthLabels = [];
    final List<double> incomeValues = [];
    final List<double> expenseValues = [];

    if (monthly != null) {
      final labels = monthly!['labels'];
      final incomes = monthly!['income'];
      final expenses = monthly!['expense'];

      if (labels is List) {
        for (final l in labels) {
          monthLabels.add(l.toString());
        }
      }
      if (incomes is List) {
        for (final v in incomes) {
          if (v is num) incomeValues.add(v.toDouble());
        }
      }
      if (expenses is List) {
        for (final v in expenses) {
          if (v is num) expenseValues.add(v.toDouble());
        }
      }
    }

    final List<_CategorySlice> categoryData = [];
    if (category != null) {
      final labels = category!['labels'];
      final values = category!['values'];
      if (labels is List && values is List) {
        final len = labels.length < values.length
            ? labels.length
            : values.length;
        for (var i = 0; i < len; i++) {
          final label = labels[i]?.toString() ?? 'Category';
          final val = values[i];
          if (val is num) {
            categoryData.add(_CategorySlice(label, val.toDouble()));
          }
        }
      }
    }

    return SingleChildScrollView(
      child: Column(
        children: [
          const SizedBox(height: 12),

          // Income vs Expense (monthly)
          Text(
            "Income vs Expense",
            style: theme.textTheme.titleMedium?.copyWith(
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 8),
          SizedBox(
            height: 260,
            child:
                (monthLabels.isEmpty ||
                    incomeValues.isEmpty ||
                    expenseValues.isEmpty)
                ? Center(
                    child: Text(
                      'No chart data yet. Add some transactions.',
                      style: theme.textTheme.bodyMedium?.copyWith(
                        color: theme.colorScheme.onSurface.withOpacity(0.6),
                      ),
                      textAlign: TextAlign.center,
                    ),
                  )
                : SfCartesianChart(
                    legend: const Legend(isVisible: true),
                    tooltipBehavior: cartesianTooltip,
                    primaryXAxis: CategoryAxis(),
                    primaryYAxis: NumericAxis(),
                    series: <CartesianSeries>[
                      LineSeries<_MonthlyPoint, String>(
                        name: 'Income',
                        dataSource: _buildMonthlyPoints(
                          monthLabels,
                          incomeValues,
                        ),
                        xValueMapper: (d, _) => d.label,
                        yValueMapper: (d, _) => d.value,
                        color: Colors.green,
                        markerSettings: const MarkerSettings(isVisible: true),
                      ),
                      LineSeries<_MonthlyPoint, String>(
                        name: 'Expense',
                        dataSource: _buildMonthlyPoints(
                          monthLabels,
                          expenseValues,
                        ),
                        xValueMapper: (d, _) => d.label,
                        yValueMapper: (d, _) => d.value,
                        color: Colors.red,
                        markerSettings: const MarkerSettings(isVisible: true),
                      ),
                    ],
                  ),
          ),

          const SizedBox(height: 28),

          // Spending by Category
          Text(
            "Spending by Category",
            style: theme.textTheme.titleMedium?.copyWith(
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 8),
          SizedBox(
            height: 260,
            child: categoryData.isEmpty
                ? Center(
                    child: Text(
                      'No category breakdown yet.',
                      style: theme.textTheme.bodyMedium?.copyWith(
                        color: theme.colorScheme.onSurface.withOpacity(0.6),
                      ),
                      textAlign: TextAlign.center,
                    ),
                  )
                : SfCircularChart(
                    legend: const Legend(isVisible: true),
                    tooltipBehavior: pieTooltip,
                    series: <CircularSeries>[
                      PieSeries<_CategorySlice, String>(
                        dataSource: categoryData,
                        xValueMapper: (d, _) => d.label,
                        yValueMapper: (d, _) => d.value,
                        dataLabelSettings: const DataLabelSettings(
                          isVisible: false,
                        ),
                      ),
                    ],
                  ),
          ),
        ],
      ),
    );
  }

  static List<_MonthlyPoint> _buildMonthlyPoints(
    List<String> labels,
    List<double> values,
  ) {
    final result = <_MonthlyPoint>[];
    final len = labels.length < values.length ? labels.length : values.length;
    for (var i = 0; i < len; i++) {
      result.add(_MonthlyPoint(labels[i], values[i]));
    }
    return result;
  }
}

class _MonthlyPoint {
  final String label;
  final double value;

  _MonthlyPoint(this.label, this.value);
}

class _CategorySlice {
  final String label;
  final double value;

  _CategorySlice(this.label, this.value);
}
