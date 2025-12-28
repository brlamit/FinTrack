import 'dart:io';

import 'package:fintrack_frontend/services/api_service.dart';
import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:open_filex/open_filex.dart';
import 'package:path_provider/path_provider.dart';
import 'package:syncfusion_flutter_charts/charts.dart';

class StatScreen extends StatefulWidget {
  final Map<String, dynamic>? rawDashboard;

  const StatScreen({super.key, this.rawDashboard});

  @override
  State<StatScreen> createState() => _StatScreenState();
}

class _StatScreenState extends State<StatScreen> {
  late DateTime _startDate;
  late DateTime _endDate;
  int _year = DateTime.now().year;

  bool _loading = false;
  String? _error;

  List<dynamic> _monthlyReport = [];
  List<dynamic> _categoryReport = [];
  List<dynamic> _yearlyReport = [];
  Map<String, dynamic>? _summary;

  final DateFormat _dateFmt = DateFormat('yyyy-MM-dd');

  @override
  void initState() {
    super.initState();
    final now = DateTime.now();
    _startDate = DateTime(now.year, now.month, 1);
    _endDate = DateTime(now.year, now.month + 1, 0);
    _loadReports();
  }

  double _toDouble(dynamic value) {
    if (value is num) return value.toDouble();
    if (value is String) return double.tryParse(value) ?? 0.0;
    return 0.0;
  }

  Future<void> _loadReports() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final monthly = await ApiService.fetchSpendingReport(
        groupBy: 'month',
        startDate: _startDate,
        endDate: _endDate,
      );
      final category = await ApiService.fetchSpendingReport(
        groupBy: 'category',
        startDate: _startDate,
        endDate: _endDate,
      );
      final yearly = await ApiService.fetchSpendingReport(
        groupBy: 'month',
        startDate: DateTime(_year, 1, 1),
        endDate: DateTime(_year, 12, 31),
      );

      setState(() {
        _monthlyReport = List<dynamic>.from(monthly['report'] as List? ?? []);
        _categoryReport = List<dynamic>.from(category['report'] as List? ?? []);
        _yearlyReport = List<dynamic>.from(yearly['report'] as List? ?? []);
        _summary = Map<String, dynamic>.from(
          (monthly['summary'] as Map?) ?? const {},
        );
        _loading = false;
      });
    } catch (e) {
      setState(() {
        _error = e.toString();
        _loading = false;
      });
    }
  }

  Future<void> _pickStartDate() async {
    final picked = await showDatePicker(
      context: context,
      initialDate: _startDate,
      firstDate: DateTime(2000),
      lastDate: DateTime(2100),
    );
    if (picked != null) {
      setState(() => _startDate = picked);
      _loadReports();
    }
  }

  Future<void> _pickEndDate() async {
    final picked = await showDatePicker(
      context: context,
      initialDate: _endDate,
      firstDate: DateTime(2000),
      lastDate: DateTime(2100),
    );
    if (picked != null) {
      setState(() => _endDate = picked);
      _loadReports();
    }
  }

  Future<void> _pickYear() async {
    final picked = await showDatePicker(
      context: context,
      initialDate: DateTime(_year, 1, 1),
      firstDate: DateTime(2000),
      lastDate: DateTime(2100),
    );
    if (picked != null) {
      setState(() => _year = picked.year);
      _loadReports();
    }
  }

  String _formatCurrency(num value) {
    return NumberFormat.compactCurrency(
      symbol: '',
      decimalDigits: 2,
    ).format(value);
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    final cartesianTooltip = TooltipBehavior(
      enable: true,
      canShowMarker: true,
      header: '',
      format: 'point.x : point.y',
    );

    final pieTooltip = TooltipBehavior(
      enable: true,
      header: '',
      format: 'point.x : point.y',
    );

    // Parse monthly income/expense chartData from dashboard (old chart)
    final List<String> monthLabels = [];
    final List<double> incomeValues = [];
    final List<double> expenseValues = [];

    final monthlyChart =
        widget.rawDashboard?['chartData']?['monthly'] as Map<String, dynamic>?;
    if (monthlyChart != null) {
      final labels = monthlyChart['labels'];
      final incomes = monthlyChart['income'];
      final expenses = monthlyChart['expense'];

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

    // Build point lists for chart when data is available
    final List<_MonthlyPoint> incomePoints = [];
    final List<_MonthlyPoint> expensePoints = [];
    int len = monthLabels.length;
    if (incomeValues.length < len) len = incomeValues.length;
    if (expenseValues.length < len) len = expenseValues.length;
    for (var i = 0; i < len; i++) {
      incomePoints.add(_MonthlyPoint(monthLabels[i], incomeValues[i]));
      expensePoints.add(_MonthlyPoint(monthLabels[i], expenseValues[i]));
    }

    final totalExpenses = _toDouble(_summary?['total_expenses']);
    final totalIncome = _toDouble(_summary?['total_income']);
    final rawCount = _summary?['transaction_count'];
    final transactionCount = rawCount is num
        ? rawCount.toInt()
        : int.tryParse(rawCount?.toString() ?? '') ?? 0;
    final netRaw = _summary?['net'];
    final net = netRaw != null
        ? _toDouble(netRaw)
        : (totalIncome - totalExpenses);

    return SafeArea(
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 16.0, vertical: 10.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: Text(
                    'Financial Reports',
                    style: theme.textTheme.titleLarge?.copyWith(
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ),
                TextButton.icon(
                  onPressed: () async {
                    try {
                      final bytes = await ApiService.downloadReportSheetPdf(
                        startDate: _startDate,
                        endDate: _endDate,
                      );
                      final dir = await getTemporaryDirectory();
                      final file = File(
                        '${dir.path}/balance_sheet_${DateTime.now().millisecondsSinceEpoch}.pdf',
                      );
                      await file.writeAsBytes(bytes, flush: true);
                      await OpenFilex.open(file.path);
                    } catch (e) {
                      if (!context.mounted) return;
                      ScaffoldMessenger.of(context).showSnackBar(
                        SnackBar(
                          content: Text('Failed to download report: $e'),
                        ),
                      );
                    }
                  },
                  icon: const Icon(Icons.download_outlined, size: 18),
                  label: const Text('Download report'),
                ),
              ],
            ),
            const SizedBox(height: 8),
            Row(
              children: [
                Expanded(
                  child: OutlinedButton(
                    onPressed: _pickStartDate,
                    child: Text('From ${_dateFmt.format(_startDate)}'),
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: OutlinedButton(
                    onPressed: _pickEndDate,
                    child: Text('To ${_dateFmt.format(_endDate)}'),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),
            if (_loading)
              const Expanded(child: Center(child: CircularProgressIndicator()))
            else if (_error != null)
              Expanded(
                child: Center(
                  child: Text(
                    'Failed to load reports',
                    style: theme.textTheme.bodyMedium?.copyWith(
                      color: theme.colorScheme.error,
                    ),
                  ),
                ),
              )
            else
              Expanded(
                child: ListView(
                  children: [
                    // Summary cards
                    Row(
                      children: [
                        _SummaryCard(
                          label: 'Total Expenses',
                          value: _formatCurrency(totalExpenses),
                          icon: Icons.wallet,
                          iconColor: Colors.red,
                        ),
                        const SizedBox(width: 8),
                        _SummaryCard(
                          label: 'Transactions',
                          value: transactionCount.toString(),
                          icon: Icons.list_alt,
                          iconColor: Colors.blue,
                        ),
                        const SizedBox(width: 8),
                        _SummaryCard(
                          label: 'Net',
                          value:
                              '${net >= 0 ? '+' : '-'}${_formatCurrency(net.abs())}',
                          icon: Icons.balance,
                          iconColor: net >= 0 ? Colors.green : Colors.red,
                        ),
                      ],
                    ),
                    const SizedBox(height: 16),
                    // Monthly Income vs Expense (old chart style)
                    _CardSection(
                      title: 'Monthly Income vs Expense',
                      child: SizedBox(
                        height: 220,
                        child:
                            (monthLabels.isEmpty ||
                                incomePoints.isEmpty ||
                                expensePoints.isEmpty)
                            ? Center(
                                child: Text(
                                  'No chart data yet. Add some transactions.',
                                  style: theme.textTheme.bodyMedium?.copyWith(
                                    color: theme.colorScheme.onSurface
                                        .withOpacity(0.6),
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
                                    dataSource: incomePoints,
                                    xValueMapper: (d, _) => d.label,
                                    yValueMapper: (d, _) => d.value,
                                    color: Colors.green,
                                    markerSettings: const MarkerSettings(
                                      isVisible: true,
                                    ),
                                  ),
                                  LineSeries<_MonthlyPoint, String>(
                                    name: 'Expense',
                                    dataSource: expensePoints,
                                    xValueMapper: (d, _) => d.label,
                                    yValueMapper: (d, _) => d.value,
                                    color: Colors.red,
                                    markerSettings: const MarkerSettings(
                                      isVisible: true,
                                    ),
                                  ),
                                ],
                              ),
                      ),
                    ),
                    const SizedBox(height: 16),
                    // Spending by category
                    _CardSection(
                      title: 'Spending by Category',
                      child: SizedBox(
                        height: 220,
                        child: SfCircularChart(
                          legend: const Legend(isVisible: true),
                          tooltipBehavior: pieTooltip,
                          series:
                              <CircularSeries<_CategoryExpenseSlice, String>>[
                                PieSeries<_CategoryExpenseSlice, String>(
                                  dataSource: _categoryReport
                                      .map(
                                        (e) => _CategoryExpenseSlice(
                                          label:
                                              (e['category']?['name']) ??
                                              e['category'] as String? ??
                                              'Other',
                                          total: _toDouble(e['total']),
                                        ),
                                      )
                                      .toList(),
                                  xValueMapper: (d, _) => d.label,
                                  yValueMapper: (d, _) => d.total,
                                  dataLabelSettings: const DataLabelSettings(
                                    isVisible: false,
                                  ),
                                ),
                              ],
                        ),
                      ),
                    ),
                    const SizedBox(height: 16),
                    // Yearly summary (bars)
                    _CardSection(
                      title: 'Yearly Summary',
                      child: Column(
                        children: [
                          Align(
                            alignment: Alignment.centerLeft,
                            child: TextButton.icon(
                              onPressed: _pickYear,
                              icon: const Icon(Icons.calendar_today, size: 16),
                              label: Text('Year $_year'),
                            ),
                          ),
                          SizedBox(
                            height: 200,
                            child: SfCartesianChart(
                              tooltipBehavior: TooltipBehavior(enable: true),
                              primaryXAxis: CategoryAxis(),
                              primaryYAxis: NumericAxis(),
                              series:
                                  <
                                    CartesianSeries<
                                      _MonthlyExpensePoint,
                                      String
                                    >
                                  >[
                                    ColumnSeries<_MonthlyExpensePoint, String>(
                                      dataSource: _yearlyReport
                                          .map(
                                            (e) => _MonthlyExpensePoint(
                                              label: e['month'] != null
                                                  ? e['month']
                                                        .toString()
                                                        .padLeft(2, '0')
                                                  : '',
                                              total: _toDouble(e['total']),
                                            ),
                                          )
                                          .toList(),
                                      xValueMapper: (d, _) => d.label,
                                      yValueMapper: (d, _) => d.total,
                                      name: 'Expenses $_year',
                                      color: theme.colorScheme.primary,
                                    ),
                                  ],
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
          ],
        ),
      ),
    );
  }
}

class _SummaryCard extends StatelessWidget {
  final String label;
  final String value;
  final IconData icon;
  final Color iconColor;

  const _SummaryCard({
    required this.label,
    required this.value,
    required this.icon,
    required this.iconColor,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Expanded(
      child: Container(
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: theme.colorScheme.surface,
          borderRadius: BorderRadius.circular(12),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withOpacity(0.04),
              blurRadius: 8,
              offset: const Offset(0, 4),
            ),
          ],
        ),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    label,
                    style: theme.textTheme.bodySmall?.copyWith(
                      color: theme.hintColor,
                    ),
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: 4),
                  Text(
                    value,
                    style: theme.textTheme.titleMedium?.copyWith(
                      fontWeight: FontWeight.bold,
                    ),
                    overflow: TextOverflow.ellipsis,
                  ),
                ],
              ),
            ),
            Icon(icon, color: iconColor),
          ],
        ),
      ),
    );
  }
}

class _CardSection extends StatelessWidget {
  final String title;
  final Widget child;

  const _CardSection({required this.title, required this.child});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: theme.colorScheme.surface,
        borderRadius: BorderRadius.circular(12),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.04),
            blurRadius: 8,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title,
            style: theme.textTheme.titleMedium?.copyWith(
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 8),
          child,
        ],
      ),
    );
  }
}

class _MonthlyExpensePoint {
  final String label;
  final double total;

  _MonthlyExpensePoint({required this.label, required this.total});
}

class _CategoryExpenseSlice {
  final String label;
  final double total;

  _CategoryExpenseSlice({required this.label, required this.total});
}

class _MonthlyPoint {
  final String label;
  final double value;

  _MonthlyPoint(this.label, this.value);
}
