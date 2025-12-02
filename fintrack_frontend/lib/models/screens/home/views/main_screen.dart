import 'dart:math';
import 'package:expense_repository/expense_repository.dart';
import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

class MainScreen extends StatelessWidget {
  final List<Expense> expenses;
  final String? userName;

  const MainScreen(this.expenses, {super.key, this.userName});

  // Total balance (income + expenses)
  double _calculateTotalExpenses(List<Expense> expenses) {
    return expenses.fold(0, (sum, e) => sum + e.amount);
  }

  // Sum of income (positive amounts)
  double _calculateIncome(List<Expense> expenses) {
    return expenses
        .where((e) => e.amount > 0)
        .fold(0, (sum, e) => sum + e.amount);
  }

  // Sum of expenses (absolute value of negative amounts)
  double _calculateExpense(List<Expense> expenses) {
    return expenses
        .where((e) => e.amount < 0)
        .fold(0, (sum, e) => sum + e.amount.abs());
  }

  @override
  Widget build(BuildContext context) {
    final totalBalance = _calculateTotalExpenses(expenses);
    final incomeTotal = _calculateIncome(expenses);
    final expenseTotal = _calculateExpense(expenses);
    final currencyFmt = NumberFormat.currency(locale: 'en_US', symbol: '\$');

    return Scaffold(
      backgroundColor: Colors.grey[100],
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 16.0, vertical: 10.0),
          child: SingleChildScrollView(
            physics: const BouncingScrollPhysics(),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // === Welcome + Balance Header ===
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          "Hello, ${userName ?? 'User'}!",
                          style: const TextStyle(
                            fontSize: 24,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          'Welcome back',
                          style: TextStyle(
                            color: Theme.of(context).colorScheme.outline,
                            fontSize: 16,
                          ),
                        ),
                      ],
                    ),
                    // Optional avatar
                    CircleAvatar(
                      radius: 24,
                      backgroundColor: Theme.of(context).colorScheme.primary,
                      child: Text(
                        (userName?.isNotEmpty == true)
                            ? userName![0].toUpperCase()
                            : 'U',
                        style: const TextStyle(
                          fontSize: 20,
                          color: Colors.white,
                        ),
                      ),
                    ),
                  ],
                ),

                const SizedBox(height: 24),

                // === Balance Cards ===
                Row(
                  children: [
                    Expanded(
                      child: _MetricCard(
                        label: 'Total Balance',
                        value: currencyFmt.format(totalBalance),
                        desc: 'Income - Expenses',
                        icon: Icons.account_balance_wallet,
                        color: totalBalance >= 0 ? Colors.green : Colors.red,
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: _MetricCard(
                        label: 'Income',
                        value: currencyFmt.format(incomeTotal),
                        desc: 'This month',
                        icon: Icons.trending_up,
                        color: Colors.green,
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: _MetricCard(
                        label: 'Expenses',
                        value: currencyFmt.format(expenseTotal),
                        desc: 'This month',
                        icon: Icons.trending_down,
                        color: Colors.red,
                      ),
                    ),
                  ],
                ),

                const SizedBox(height: 24),

                // === Quick Actions ===
                Text(
                  'Quick Actions',
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.w600,
                    color: Theme.of(context).colorScheme.onSurface,
                  ),
                ),
                const SizedBox(height: 12),
                Wrap(
                  spacing: 12,
                  runSpacing: 12,
                  children: [
                    ElevatedButton.icon(
                      onPressed: () {},
                      icon: const Icon(CupertinoIcons.add_circled_solid),
                      label: const Text('Add Transaction'),
                    ),
                    OutlinedButton.icon(
                      onPressed: () {},
                      icon: const Icon(CupertinoIcons.chart_bar),
                      label: const Text('Manage Budgets'),
                    ),
                    OutlinedButton.icon(
                      onPressed: () {},
                      icon: const Icon(CupertinoIcons.person_2),
                      label: const Text('My Groups'),
                    ),
                    OutlinedButton.icon(
                      onPressed: () {},
                      icon: const Icon(CupertinoIcons.doc_text),
                      label: const Text('View Reports'),
                    ),
                  ],
                ),

                const SizedBox(height: 32),

                // === Spending Insights + Top Category ===
                Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Expanded(
                      flex: 3,
                      child: Card(
                        child: Padding(
                          padding: const EdgeInsets.all(16.0),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const Text(
                                'Spending Insights',
                                style: TextStyle(
                                  fontSize: 16,
                                  fontWeight: FontWeight.w600,
                                ),
                              ),
                              const SizedBox(height: 8),
                              Text(
                                'Snapshot for recent activity',
                                style: TextStyle(
                                  color: Theme.of(context).colorScheme.outline,
                                ),
                              ),
                              const SizedBox(height: 16),
                              Wrap(
                                spacing: 12,
                                runSpacing: 12,
                                children: [
                                  _SmallInsight(
                                    label: 'Net Flow',
                                    value: currencyFmt.format(totalBalance),
                                  ),
                                  _SmallInsight(
                                    label: 'Avg Expense',
                                    value: currencyFmt.format(
                                      expenseTotal /
                                          (expenses.isEmpty
                                              ? 1
                                              : expenses.length),
                                    ),
                                  ),
                                  _SmallInsight(
                                    label: 'Transactions',
                                    value: '${expenses.length}',
                                  ),
                                  _SmallInsight(
                                    label: 'Top Category',
                                    value: _topCategoryLabel(expenses),
                                  ),
                                ],
                              ),
                            ],
                          ),
                        ),
                      ),
                    ),
                    const SizedBox(width: 12),
                    SizedBox(
                      width: 280,
                      child: Card(
                        child: Padding(
                          padding: const EdgeInsets.all(16.0),
                          child: Column(
                            children: [
                              const Text(
                                'Top Spending Focus',
                                style: TextStyle(
                                  fontSize: 16,
                                  fontWeight: FontWeight.w600,
                                ),
                              ),
                              const SizedBox(height: 8),
                              Text(
                                'Based on recent activity',
                                style: TextStyle(
                                  color: Theme.of(context).colorScheme.outline,
                                ),
                              ),
                              const SizedBox(height: 16),
                              Text(
                                _topCategoryLabel(expenses),
                                style: const TextStyle(
                                  fontSize: 18,
                                  fontWeight: FontWeight.w600,
                                ),
                              ),
                              const SizedBox(height: 8),
                              Text(
                                _topCategoryAmount(expenses, currencyFmt),
                                style: const TextStyle(
                                  fontSize: 28,
                                  fontWeight: FontWeight.bold,
                                  color: Colors.red,
                                ),
                              ),
                              const SizedBox(height: 8),
                              Chip(
                                label: Text(_topCategoryShareLabel(expenses)),
                              ),
                              const SizedBox(height: 12),
                              Text(
                                'Use this insight to plan your next budget move.',
                                style: TextStyle(
                                  color: Theme.of(context).colorScheme.outline,
                                  fontSize: 12,
                                ),
                                textAlign: TextAlign.center,
                              ),
                            ],
                          ),
                        ),
                      ),
                    ),
                  ],
                ),

                const SizedBox(height: 24),

                // === Charts Placeholder ===
                Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Expanded(
                      flex: 3,
                      child: _ChartCard(title: 'Income vs Expense'),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      flex: 1,
                      child: _ChartCard(title: 'Category Breakdown'),
                    ),
                  ],
                ),

                const SizedBox(height: 24),

                // === Budget Status ===
                Card(
                  child: Padding(
                    padding: const EdgeInsets.all(16.0),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            const Text(
                              'Budget Status',
                              style: TextStyle(
                                fontSize: 16,
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                            TextButton(
                              onPressed: () {},
                              child: const Text('Manage Budgets'),
                            ),
                          ],
                        ),
                        const SizedBox(height: 16),
                        Container(
                          width: double.infinity,
                          padding: const EdgeInsets.symmetric(vertical: 20),
                          child: Column(
                            children: [
                              Text(
                                'You don\'t have any active budgets yet.',
                                style: TextStyle(
                                  color: Theme.of(context).colorScheme.outline,
                                ),
                              ),
                              const SizedBox(height: 12),
                              ElevatedButton(
                                onPressed: () {},
                                child: const Text('Create your first budget'),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                ),

                const SizedBox(height: 32),

                // === Recent Transactions ===
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Text(
                      'Recent Transactions',
                      style: TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    GestureDetector(
                      onTap: () {},
                      child: Text(
                        'View All',
                        style: TextStyle(
                          color: Theme.of(context).colorScheme.primary,
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 16),

                expenses.isEmpty
                    ? Center(
                        child: Padding(
                          padding: const EdgeInsets.symmetric(vertical: 40),
                          child: Text(
                            'No transactions yet',
                            style: TextStyle(
                              color: Theme.of(context).colorScheme.outline,
                              fontSize: 16,
                            ),
                          ),
                        ),
                      )
                    : ListView.builder(
                        shrinkWrap: true,
                        physics: const NeverScrollableScrollPhysics(),
                        itemCount: expenses.length.clamp(
                          0,
                          5,
                        ), // Show only recent 5
                        itemBuilder: (context, i) {
                          final e = expenses[i];
                          return Padding(
                            padding: const EdgeInsets.only(bottom: 12.0),
                            child: Container(
                              decoration: BoxDecoration(
                                color: Colors.white,
                                borderRadius: BorderRadius.circular(12),
                                boxShadow: [
                                  BoxShadow(
                                    color: Colors.black.withOpacity(0.05),
                                    blurRadius: 8,
                                  ),
                                ],
                              ),
                              child: ListTile(
                                leading: Container(
                                  width: 50,
                                  height: 50,
                                  decoration: BoxDecoration(
                                    color: Color(e.category.color),
                                    shape: BoxShape.circle,
                                  ),
                                  child: Image.asset(
                                    'assets/${e.category.icon}.png',
                                    scale: 2,
                                    color: Colors.white,
                                  ),
                                ),
                                title: Text(
                                  e.category.name,
                                  style: const TextStyle(
                                    fontWeight: FontWeight.w500,
                                  ),
                                ),
                                subtitle: Text(
                                  DateFormat('dd MMM yyyy').format(e.date),
                                ),
                                trailing: Text(
                                  currencyFmt.format(e.amount),
                                  style: TextStyle(
                                    fontWeight: FontWeight.w600,
                                    color: e.amount < 0
                                        ? Colors.red
                                        : Colors.green,
                                  ),
                                ),
                              ),
                            ),
                          );
                        },
                      ),

                const SizedBox(height: 20),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

// Reusable Metric Card
class _MetricCard extends StatelessWidget {
  final String label, value, desc;
  final IconData icon;
  final Color color;

  const _MetricCard({
    required this.label,
    required this.value,
    required this.desc,
    required this.icon,
    required this.color,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      elevation: 2,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      child: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Row(
          children: [
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    label,
                    style: TextStyle(
                      color: Theme.of(context).colorScheme.outline,
                    ),
                  ),
                  const SizedBox(height: 6),
                  Text(
                    value,
                    style: TextStyle(
                      fontSize: 20,
                      fontWeight: FontWeight.bold,
                      color: color,
                    ),
                  ),
                  const SizedBox(height: 6),
                  Text(
                    desc,
                    style: TextStyle(
                      color: Theme.of(context).colorScheme.outline,
                      fontSize: 12,
                    ),
                  ),
                ],
              ),
            ),
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: color.withOpacity(0.12),
                shape: BoxShape.circle,
              ),
              child: Icon(icon, color: color),
            ),
          ],
        ),
      ),
    );
  }
}

// Small Insight Tile
class _SmallInsight extends StatelessWidget {
  final String label, value;
  const _SmallInsight({required this.label, required this.value});

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: 140,
      child: Card(
        child: Padding(
          padding: const EdgeInsets.all(12.0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                label,
                style: TextStyle(
                  color: Theme.of(context).colorScheme.outline,
                  fontSize: 12,
                ),
              ),
              const SizedBox(height: 4),
              Text(
                value,
                style: const TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.w600,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

// Chart Placeholder
class _ChartCard extends StatelessWidget {
  final String title;
  const _ChartCard({required this.title});

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16.0),
        child: SizedBox(
          height: 260,
          child: Center(
            child: Text(
              '$title\n(chart placeholder)',
              textAlign: TextAlign.center,
            ),
          ),
        ),
      ),
    );
  }
}

// Top category helpers (unchanged but cleaned)
String _topCategoryLabel(List<Expense> expenses) {
  final totals = <String, double>{};
  for (var e in expenses) {
    if (e.amount < 0) {
      totals[e.category.name] = (totals[e.category.name] ?? 0) + e.amount.abs();
    }
  }
  if (totals.isEmpty) return 'N/A';
  return totals.entries.reduce((a, b) => a.value > b.value ? a : b).key;
}

String _topCategoryAmount(List<Expense> expenses, NumberFormat fmt) {
  final totals = <String, double>{};
  for (var e in expenses) {
    if (e.amount < 0) {
      totals[e.category.name] = (totals[e.category.name] ?? 0) + e.amount.abs();
    }
  }
  if (totals.isEmpty) return fmt.format(0);
  return fmt.format(
    totals.entries.reduce((a, b) => a.value > b.value ? a : b).value,
  );
}

String _topCategoryShareLabel(List<Expense> expenses) {
  final totals = <String, double>{};
  double totalExpense = 0;
  for (var e in expenses) {
    if (e.amount < 0) {
      final amount = e.amount.abs();
      totalExpense += amount;
      totals[e.category.name] = (totals[e.category.name] ?? 0) + amount;
    }
  }
  if (totalExpense == 0) return '0%';
  final top = totals.entries.reduce((a, b) => a.value > b.value ? a : b);
  return '${(top.value / totalExpense * 100).toStringAsFixed(0)}% of total';
}
