import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

import '../../../services/api_service.dart';

class BudgetsScreen extends StatefulWidget {
  const BudgetsScreen({super.key});

  @override
  State<BudgetsScreen> createState() => _BudgetsScreenState();
}

class _BudgetsScreenState extends State<BudgetsScreen> {
  bool _loading = true;
  String? _error;
  List<dynamic> _budgets = [];

  final DateFormat _dateFmt = DateFormat('yyyy-MM-dd');

  @override
  void initState() {
    super.initState();
    _loadBudgets();
  }

  Future<void> _loadBudgets() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final data = await ApiService.fetchBudgets(activeOnly: false);
      setState(() {
        _budgets = data;
      });
    } catch (e) {
      setState(() {
        _error = e.toString();
      });
    } finally {
      setState(() {
        _loading = false;
      });
    }
  }

  String _formatCurrency(dynamic value) {
    double amount = 0;
    if (value is num) {
      amount = value.toDouble();
    } else if (value is String) {
      amount = double.tryParse(value) ?? 0;
    }
    return NumberFormat.compactCurrency(
      symbol: '',
      decimalDigits: 2,
    ).format(amount);
  }

  Future<void> _openCreateBudget() async {
    final created = await Navigator.push<bool>(
      context,
      MaterialPageRoute(builder: (_) => const EditBudgetScreen()),
    );
    if (created == true) {
      _loadBudgets();
    }
  }

  Future<void> _openEditBudget(Map<String, dynamic> budget) async {
    final updated = await Navigator.push<bool>(
      context,
      MaterialPageRoute(
        builder: (_) => EditBudgetScreen(initialBudget: budget),
      ),
    );
    if (updated == true) {
      _loadBudgets();
    }
  }

  Future<void> _deleteBudget(int id) async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Delete budget'),
        content: const Text('Are you sure you want to delete this budget?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: const Text('Cancel'),
          ),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Delete'),
          ),
        ],
      ),
    );

    if (confirm != true) return;

    try {
      await ApiService.deleteBudget(id);
      if (!mounted) return;
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(const SnackBar(content: Text('Budget deleted')));
      _loadBudgets();
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text('Failed to delete budget: $e')));
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Scaffold(
      appBar: AppBar(
        title: const Text('My Budgets'),
        backgroundColor: theme.colorScheme.primary,
        foregroundColor: theme.colorScheme.onPrimary,
        actions: [
          IconButton(
            onPressed: _loadBudgets,
            icon: const Icon(Icons.refresh),
            tooltip: 'Refresh',
          ),
        ],
      ),
      floatingActionButton: FloatingActionButton(
        onPressed: _openCreateBudget,
        backgroundColor: theme.colorScheme.primary,
        foregroundColor: theme.colorScheme.onPrimary,
        elevation: 4,
        child: const Icon(Icons.add),
      ),
      body: Container(
        decoration: BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
            colors: [
              theme.colorScheme.primaryContainer.withOpacity(0.1),
              theme.colorScheme.surface,
            ],
          ),
        ),
        child: SafeArea(
          child: _loading
              ? const Center(child: CircularProgressIndicator())
              : _error != null
              ? Center(
                  child: Padding(
                    padding: const EdgeInsets.all(16.0),
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(
                          Icons.error_outline,
                          size: 48,
                          color: theme.colorScheme.error,
                        ),
                        const SizedBox(height: 16),
                        Text(
                          'Failed to load budgets',
                          style: theme.textTheme.headlineSmall?.copyWith(
                            color: theme.colorScheme.error,
                          ),
                        ),
                        const SizedBox(height: 8),
                        Text(
                          _error!,
                          textAlign: TextAlign.center,
                          style: theme.textTheme.bodyMedium?.copyWith(
                            color: theme.colorScheme.onSurface.withOpacity(0.7),
                          ),
                        ),
                        const SizedBox(height: 16),
                        ElevatedButton(
                          onPressed: _loadBudgets,
                          child: const Text('Try Again'),
                        ),
                      ],
                    ),
                  ),
                )
              : _budgets.isEmpty
              ? Center(
                  child: Padding(
                    padding: const EdgeInsets.all(16.0),
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(
                          Icons.account_balance_wallet,
                          size: 64,
                          color: theme.colorScheme.primary.withOpacity(0.5),
                        ),
                        const SizedBox(height: 16),
                        Text(
                          'No budgets yet',
                          style: theme.textTheme.headlineSmall?.copyWith(
                            color: theme.colorScheme.onSurface,
                          ),
                        ),
                        const SizedBox(height: 8),
                        Text(
                          'Create your first budget to start tracking spending limits and stay on top of your finances.',
                          textAlign: TextAlign.center,
                          style: theme.textTheme.bodyMedium?.copyWith(
                            color: theme.colorScheme.onSurface.withOpacity(0.7),
                          ),
                        ),
                        const SizedBox(height: 24),
                        ElevatedButton.icon(
                          onPressed: _openCreateBudget,
                          icon: const Icon(Icons.add),
                          label: const Text('Create Budget'),
                          style: ElevatedButton.styleFrom(
                            padding: const EdgeInsets.symmetric(
                              horizontal: 24,
                              vertical: 12,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                )
              : RefreshIndicator(
                  onRefresh: _loadBudgets,
                  child: ListView.separated(
                    padding: const EdgeInsets.all(16.0),
                    itemBuilder: (ctx, index) {
                      final b = _budgets[index] as Map<String, dynamic>;
                      final name = b['name']?.toString() ?? 'Budget';
                      final amount = b['amount'] ?? b['limit_amount'];
                      final current = b['current_spending'] ?? b['spent'];
                      final remaining = b['remaining_amount'];
                      final period = b['period']?.toString() ?? 'monthly';
                      final start = b['start_date']?.toString();
                      final end = b['end_date']?.toString();
                      final progress = (b['spending_percentage'] is num)
                          ? (b['spending_percentage'] as num).toDouble()
                          : 0.0;
                      final isActive = b['is_active'] == true;

                      Color barColor;
                      if (progress >= 100) {
                        barColor = Colors.red;
                      } else if (progress >= 85) {
                        barColor = Colors.orange;
                      } else {
                        barColor = Colors.green;
                      }

                      return Card(
                        elevation: 2,
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(16),
                        ),
                        child: InkWell(
                          onTap: () => _openEditBudget(b),
                          borderRadius: BorderRadius.circular(16),
                          child: Padding(
                            padding: const EdgeInsets.all(16.0),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Row(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Expanded(
                                      child: Column(
                                        crossAxisAlignment:
                                            CrossAxisAlignment.start,
                                        children: [
                                          Text(
                                            name,
                                            style: theme.textTheme.titleMedium
                                                ?.copyWith(
                                                  fontWeight: FontWeight.bold,
                                                ),
                                          ),
                                          const SizedBox(height: 4),
                                          Text(
                                            '${_formatCurrency(current)}/${_formatCurrency(amount)} used',
                                            style: theme.textTheme.bodySmall
                                                ?.copyWith(
                                                  color: theme
                                                      .colorScheme
                                                      .onSurface
                                                      .withOpacity(0.7),
                                                ),
                                          ),
                                          if (remaining != null) ...[
                                            const SizedBox(height: 2),
                                            Text(
                                              'Remaining: ${_formatCurrency(remaining)}',
                                              style: theme.textTheme.bodySmall
                                                  ?.copyWith(
                                                    color: theme
                                                        .colorScheme
                                                        .onSurface
                                                        .withOpacity(0.7),
                                                  ),
                                            ),
                                          ],
                                          const SizedBox(height: 6),
                                          LinearProgressIndicator(
                                            value:
                                                (progress.clamp(0, 100)) / 100,
                                            minHeight: 6,
                                            backgroundColor: theme
                                                .colorScheme
                                                .outlineVariant
                                                ?.withOpacity(0.2),
                                            valueColor: AlwaysStoppedAnimation(
                                              barColor,
                                            ),
                                          ),
                                          const SizedBox(height: 4),
                                          Text(
                                            '${progress.toStringAsFixed(1)}% of budget',
                                            style: theme.textTheme.bodySmall
                                                ?.copyWith(color: barColor),
                                          ),
                                          const SizedBox(height: 4),
                                          Text(
                                            'Period: $period'
                                            '${start != null && end != null ? ' | ${_dateFmt.format(DateTime.parse(start))} - ${_dateFmt.format(DateTime.parse(end))}' : ''}',
                                            style: theme.textTheme.bodySmall
                                                ?.copyWith(
                                                  color: theme
                                                      .colorScheme
                                                      .onSurface
                                                      .withOpacity(0.6),
                                                ),
                                          ),
                                        ],
                                      ),
                                    ),
                                    const SizedBox(width: 8),
                                    Column(
                                      children: [
                                        IconButton(
                                          icon: const Icon(
                                            Icons.delete_outline,
                                            size: 20,
                                          ),
                                          onPressed: () =>
                                              _deleteBudget(b['id'] as int),
                                        ),
                                        const SizedBox(height: 4),
                                        Container(
                                          padding: const EdgeInsets.symmetric(
                                            horizontal: 8,
                                            vertical: 4,
                                          ),
                                          decoration: BoxDecoration(
                                            color: isActive
                                                ? Colors.green.shade50
                                                : Colors.grey.shade300,
                                            borderRadius: BorderRadius.circular(
                                              12,
                                            ),
                                          ),
                                          child: Text(
                                            isActive ? 'Active' : 'Inactive',
                                            style: theme.textTheme.labelSmall
                                                ?.copyWith(
                                                  color: isActive
                                                      ? Colors.green.shade800
                                                      : Colors.grey.shade700,
                                                ),
                                          ),
                                        ),
                                      ],
                                    ),
                                  ],
                                ),
                              ],
                            ),
                          ),
                        ),
                      );
                    },
                    separatorBuilder: (_, __) => const SizedBox(height: 8),
                    itemCount: _budgets.length,
                  ),
                ),
        ),
      ),
    );
  }
}

class EditBudgetScreen extends StatefulWidget {
  final Map<String, dynamic>? initialBudget;

  const EditBudgetScreen({super.key, this.initialBudget});

  @override
  State<EditBudgetScreen> createState() => _EditBudgetScreenState();
}

class _EditBudgetScreenState extends State<EditBudgetScreen> {
  final _formKey = GlobalKey<FormState>();

  late TextEditingController _nameCtrl;
  late TextEditingController _amountCtrl;

  String _period = 'monthly';
  DateTime _startDate = DateTime.now();
  DateTime _endDate = DateTime.now().add(const Duration(days: 30));
  bool _isActive = true;

  final DateFormat _dateFmt = DateFormat('yyyy-MM-dd');

  bool _saving = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    final b = widget.initialBudget;
    _nameCtrl = TextEditingController(
      text: b != null ? b['name']?.toString() : '',
    );
    _amountCtrl = TextEditingController(
      text: b != null ? (b['amount'] ?? b['limit_amount'])?.toString() : '',
    );
    if (b != null) {
      _period = b['period']?.toString() ?? 'monthly';
      if (b['start_date'] != null) {
        _startDate =
            DateTime.tryParse(b['start_date'].toString()) ?? _startDate;
      }
      if (b['end_date'] != null) {
        _endDate = DateTime.tryParse(b['end_date'].toString()) ?? _endDate;
      }
      _isActive = b['is_active'] == null ? true : b['is_active'] == true;
    }
  }

  @override
  void dispose() {
    _nameCtrl.dispose();
    _amountCtrl.dispose();
    super.dispose();
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
    }
  }

  Future<void> _save() async {
    if (!_formKey.currentState!.validate()) return;

    final amount = double.tryParse(_amountCtrl.text.trim());
    if (amount == null || amount <= 0) {
      setState(() => _error = 'Please enter a valid amount');
      return;
    }

    setState(() {
      _saving = true;
      _error = null;
    });

    try {
      if (widget.initialBudget == null) {
        await ApiService.createBudget(
          name: _nameCtrl.text.trim(),
          amount: amount,
          period: _period,
          startDate: _startDate,
          endDate: _endDate,
        );
      } else {
        await ApiService.updateBudget(
          id: widget.initialBudget!['id'] as int,
          name: _nameCtrl.text.trim(),
          amount: amount,
          period: _period,
          startDate: _startDate,
          endDate: _endDate,
          isActive: _isActive,
        );
      }

      if (!mounted) return;
      Navigator.pop(context, true);
    } catch (e) {
      setState(() => _error = e.toString());
    } finally {
      if (mounted) {
        setState(() => _saving = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final isEdit = widget.initialBudget != null;

    return Scaffold(
      appBar: AppBar(
        title: Text(isEdit ? 'Edit Budget' : 'New Budget'),
        backgroundColor: theme.colorScheme.primary,
        foregroundColor: theme.colorScheme.onPrimary,
      ),
      body: Container(
        decoration: BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
            colors: [
              theme.colorScheme.primaryContainer.withOpacity(0.1),
              theme.colorScheme.surface,
            ],
          ),
        ),
        child: SafeArea(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(16.0),
            child: Form(
              key: _formKey,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  if (_error != null) ...[
                    Container(
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: theme.colorScheme.errorContainer,
                        borderRadius: BorderRadius.circular(8),
                        border: Border.all(
                          color: theme.colorScheme.error.withOpacity(0.3),
                        ),
                      ),
                      child: Row(
                        children: [
                          Icon(
                            Icons.error_outline,
                            color: theme.colorScheme.error,
                            size: 20,
                          ),
                          const SizedBox(width: 8),
                          Expanded(
                            child: Text(
                              _error!,
                              style: theme.textTheme.bodyMedium?.copyWith(
                                color: theme.colorScheme.error,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 16),
                  ],
                  _ModernFormField(
                    label: 'Budget Name',
                    required: true,
                    child: TextFormField(
                      controller: _nameCtrl,
                      decoration: InputDecoration(
                        hintText: 'Enter budget name',
                        border: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(12),
                        ),
                        filled: true,
                        fillColor: theme.colorScheme.surface,
                      ),
                      validator: (v) {
                        if (v == null || v.trim().isEmpty) {
                          return 'Please enter a budget name';
                        }
                        return null;
                      },
                    ),
                  ),
                  _ModernFormField(
                    label: 'Amount',
                    required: true,
                    child: TextFormField(
                      controller: _amountCtrl,
                      keyboardType: const TextInputType.numberWithOptions(
                        decimal: true,
                      ),
                      decoration: InputDecoration(
                        hintText: '0.00',
                        prefixIcon: Icon(
                          Icons.attach_money,
                          color: theme.colorScheme.primary,
                        ),
                        border: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(12),
                        ),
                        filled: true,
                        fillColor: theme.colorScheme.surface,
                      ),
                      validator: (v) {
                        if (v == null || v.trim().isEmpty) {
                          return 'Please enter an amount';
                        }
                        final amount = double.tryParse(v.trim());
                        if (amount == null || amount <= 0) {
                          return 'Please enter a valid amount';
                        }
                        return null;
                      },
                    ),
                  ),
                  _ModernFormField(
                    label: 'Period',
                    required: true,
                    child: DropdownButtonFormField<String>(
                      value: _period,
                      decoration: InputDecoration(
                        hintText: 'Select period',
                        border: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(12),
                        ),
                        filled: true,
                        fillColor: theme.colorScheme.surface,
                      ),
                      items: const [
                        DropdownMenuItem(
                          value: 'weekly',
                          child: Text('Weekly'),
                        ),
                        DropdownMenuItem(
                          value: 'monthly',
                          child: Text('Monthly'),
                        ),
                        DropdownMenuItem(
                          value: 'quarterly',
                          child: Text('Quarterly'),
                        ),
                        DropdownMenuItem(
                          value: 'yearly',
                          child: Text('Yearly'),
                        ),
                      ],
                      onChanged: (v) {
                        if (v == null) return;
                        setState(() => _period = v);
                      },
                      validator: (v) {
                        if (v == null || v.isEmpty) {
                          return 'Please select a period';
                        }
                        return null;
                      },
                    ),
                  ),
                  _ModernFormField(
                    label: 'Date Range',
                    required: true,
                    child: Row(
                      children: [
                        Expanded(
                          child: OutlinedButton.icon(
                            onPressed: _pickStartDate,
                            icon: Icon(
                              Icons.calendar_today,
                              color: theme.colorScheme.primary,
                            ),
                            label: Text(_dateFmt.format(_startDate)),
                            style: OutlinedButton.styleFrom(
                              padding: const EdgeInsets.symmetric(
                                horizontal: 16,
                                vertical: 12,
                              ),
                              side: BorderSide(
                                color: theme.colorScheme.outline,
                              ),
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(12),
                              ),
                            ),
                          ),
                        ),
                        const SizedBox(width: 8),
                        Icon(
                          Icons.arrow_forward,
                          color: theme.colorScheme.primary,
                        ),
                        const SizedBox(width: 8),
                        Expanded(
                          child: OutlinedButton.icon(
                            onPressed: _pickEndDate,
                            icon: Icon(
                              Icons.calendar_today,
                              color: theme.colorScheme.primary,
                            ),
                            label: Text(_dateFmt.format(_endDate)),
                            style: OutlinedButton.styleFrom(
                              padding: const EdgeInsets.symmetric(
                                horizontal: 16,
                                vertical: 12,
                              ),
                              side: BorderSide(
                                color: theme.colorScheme.outline,
                              ),
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(12),
                              ),
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                  if (isEdit)
                    _ModernFormField(
                      label: 'Status',
                      child: SwitchListTile(
                        value: _isActive,
                        title: Text(
                          'Active Budget',
                          style: theme.textTheme.bodyLarge,
                        ),
                        subtitle: Text(
                          _isActive
                              ? 'This budget is currently active'
                              : 'This budget is inactive',
                          style: theme.textTheme.bodySmall,
                        ),
                        onChanged: (v) => setState(() => _isActive = v),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(12),
                        ),
                        tileColor: theme.colorScheme.surface,
                      ),
                    ),
                  const SizedBox(height: 32),
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton(
                      onPressed: _saving ? null : _save,
                      style: ElevatedButton.styleFrom(
                        padding: const EdgeInsets.symmetric(vertical: 16),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(12),
                        ),
                        backgroundColor: theme.colorScheme.primary,
                        foregroundColor: theme.colorScheme.onPrimary,
                        elevation: 2,
                      ),
                      child: _saving
                          ? const SizedBox(
                              width: 24,
                              height: 24,
                              child: CircularProgressIndicator(
                                strokeWidth: 2,
                                valueColor: AlwaysStoppedAnimation<Color>(
                                  Colors.white,
                                ),
                              ),
                            )
                          : Row(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                Icon(isEdit ? Icons.save : Icons.add),
                                const SizedBox(width: 8),
                                Text(
                                  isEdit ? 'Save Changes' : 'Create Budget',
                                  style: theme.textTheme.titleMedium?.copyWith(
                                    fontWeight: FontWeight.w600,
                                  ),
                                ),
                              ],
                            ),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}

class _ModernFormField extends StatelessWidget {
  const _ModernFormField({
    required this.label,
    required this.child,
    this.required = false,
  });

  final String label;
  final Widget child;
  final bool required;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.only(bottom: 8),
          child: RichText(
            text: TextSpan(
              text: label,
              style: theme.textTheme.titleSmall?.copyWith(
                fontWeight: FontWeight.w600,
                color: theme.colorScheme.onSurface,
              ),
              children: required
                  ? [
                      TextSpan(
                        text: ' *',
                        style: TextStyle(
                          color: theme.colorScheme.error,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ]
                  : null,
            ),
          ),
        ),
        child,
        const SizedBox(height: 16),
      ],
    );
  }
}
