import 'package:flutter/material.dart';
import 'package:flutter/cupertino.dart';
import '../../../services/api_service.dart';

class TransactionListScreen extends StatefulWidget {
  final Future<void> Function()? onAddTransaction;

  const TransactionListScreen({super.key, this.onAddTransaction});

  @override
  State<TransactionListScreen> createState() => _TransactionListScreenState();
}

class _TransactionListScreenState extends State<TransactionListScreen> {
  List<dynamic> _allTransactions = [];
  List<dynamic> _filteredTransactions = [];
  String _searchQuery = '';
  String _selectedFilter = 'All';
  final List<String> _filterOptions = ['All', 'Income', 'Expense'];
  bool _isLoading = true;
  String? _errorMessage;

  @override
  void initState() {
    super.initState();
    _loadAllTransactions();
  }

  Future<void> _loadAllTransactions() async {
    try {
      setState(() {
        _isLoading = true;
        _errorMessage = null;
      });

      // Check if user is authenticated
      if (ApiService.token == null || ApiService.token!.isEmpty) {
        throw Exception('User not authenticated. Please log in again.');
      }

      print(
        'Loading transactions with token: ${ApiService.token!.substring(0, 20)}...',
      ); // Debug log

      final transactions = await ApiService.fetchAllTransactionsApi();

      print('Loaded ${transactions.length} transactions'); // Debug log

      setState(() {
        _allTransactions = transactions;
        _filteredTransactions = transactions;
        _isLoading = false;
      });
    } catch (e) {
      print('Error loading transactions: $e'); // Debug log
      setState(() {
        _errorMessage = e.toString();
        _isLoading = false;
      });
    }
  }

  void _filterTransactions() {
    setState(() {
      _filteredTransactions = _allTransactions.where((raw) {
        final tx = raw as Map<String, dynamic>;
        final isIncome = (tx['is_income'] as bool?) ?? (tx['type'] == 'income');
        final typeLabel =
            (tx['type'] as String?) ?? (isIncome ? 'income' : 'expense');
        final categoryName = (tx['category_name'] as String?) ?? 'Category';
        final displayAmount = (tx['display_amount'] as dynamic?) ?? '';
        final displayDate = (tx['display_date'] as dynamic?) ?? '';

        // Filter by type
        bool typeMatch = true;
        if (_selectedFilter != 'All') {
          typeMatch = _selectedFilter.toLowerCase() == typeLabel.toLowerCase();
        }

        // Filter by search query
        bool searchMatch = true;
        if (_searchQuery.isNotEmpty) {
          searchMatch =
              categoryName.toLowerCase().contains(_searchQuery.toLowerCase()) ||
              displayAmount.toLowerCase().contains(
                _searchQuery.toLowerCase(),
              ) ||
              displayDate.toLowerCase().contains(_searchQuery.toLowerCase());
        }

        return typeMatch && searchMatch;
      }).toList();
    });
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Scaffold(
      backgroundColor: theme.colorScheme.surface,
      appBar: AppBar(
        backgroundColor: theme.colorScheme.surface,
        elevation: 0,
        title: Text(
          'All Transactions',
          style: theme.textTheme.titleLarge?.copyWith(
            fontWeight: FontWeight.bold,
            color: theme.colorScheme.onSurface,
          ),
        ),
        leading: IconButton(
          onPressed: () => Navigator.of(context).pop(),
          icon: Icon(CupertinoIcons.back, color: theme.colorScheme.onSurface),
        ),
        actions: [
          IconButton(
            onPressed: widget.onAddTransaction,
            icon: Icon(Icons.add_circle, color: theme.colorScheme.primary),
          ),
        ],
      ),
      body: Column(
        children: [
          // Search and Filter Bar
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: theme.colorScheme.surface,
              border: Border(
                bottom: BorderSide(
                  color: theme.colorScheme.outline.withValues(alpha: 26),
                  width: 1,
                ),
              ),
            ),
            child: Column(
              children: [
                // Search Bar
                TextField(
                  onChanged: (value) {
                    _searchQuery = value;
                    _filterTransactions();
                  },
                  decoration: InputDecoration(
                    hintText: 'Search transactions...',
                    prefixIcon: Icon(
                      CupertinoIcons.search,
                      color: theme.colorScheme.onSurface.withValues(alpha: 128),
                    ),
                    filled: true,
                    fillColor: theme.colorScheme.surfaceContainerHighest
                        .withValues(alpha: 26),
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: BorderSide.none,
                    ),
                    contentPadding: const EdgeInsets.symmetric(
                      horizontal: 16,
                      vertical: 12,
                    ),
                  ),
                  style: theme.textTheme.bodyMedium?.copyWith(
                    color: theme.colorScheme.onSurface,
                  ),
                ),
                const SizedBox(height: 12),
                // Filter Chips
                Row(
                  children: _filterOptions.map((filter) {
                    final isSelected = _selectedFilter == filter;
                    return Container(
                      margin: const EdgeInsets.only(right: 8),
                      child: FilterChip(
                        label: Text(
                          filter,
                          style: theme.textTheme.bodySmall?.copyWith(
                            color: isSelected
                                ? theme.colorScheme.onPrimary
                                : theme.colorScheme.onSurface,
                            fontWeight: isSelected
                                ? FontWeight.w600
                                : FontWeight.normal,
                          ),
                        ),
                        selected: isSelected,
                        onSelected: (selected) {
                          setState(() {
                            _selectedFilter = filter;
                            _filterTransactions();
                          });
                        },
                        backgroundColor: theme
                            .colorScheme
                            .surfaceContainerHighest
                            .withValues(alpha: 26),
                        selectedColor: theme.colorScheme.primary,
                        checkmarkColor: theme.colorScheme.onPrimary,
                      ),
                    );
                  }).toList(),
                ),
              ],
            ),
          ),

          // Transaction Summary
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            decoration: BoxDecoration(
              color: theme.colorScheme.surfaceContainerLowest.withValues(
                alpha: 26,
              ),
              border: Border(
                bottom: BorderSide(
                  color: theme.colorScheme.outline.withValues(alpha: 26),
                  width: 1,
                ),
              ),
            ),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceAround,
              children: [
                _buildSummaryItem(
                  'Total',
                  '${_filteredTransactions.length}',
                  theme.colorScheme.primary,
                  theme,
                ),
                _buildSummaryItem(
                  'Income',
                  '${_filteredTransactions.where((tx) => (tx['is_income'] as bool?) ?? (tx['type'] == 'income')).length}',
                  theme.colorScheme.secondary,
                  theme,
                ),
                _buildSummaryItem(
                  'Expense',
                  '${_filteredTransactions.where((tx) => !((tx['is_income'] as bool?) ?? (tx['type'] == 'income'))).length}',
                  theme.colorScheme.error,
                  theme,
                ),
              ],
            ),
          ),

          // Transactions List
          Expanded(
            child: _isLoading
                ? _buildLoadingState(theme)
                : _errorMessage != null
                ? _buildErrorState(theme)
                : _filteredTransactions.isEmpty
                ? _buildEmptyState(theme)
                : RefreshIndicator(
                    onRefresh: _loadAllTransactions,
                    color: theme.colorScheme.primary,
                    backgroundColor: theme.colorScheme.surface,
                    child: ListView.builder(
                      padding: const EdgeInsets.all(16),
                      itemCount: _filteredTransactions.length,
                      itemBuilder: (context, index) {
                        final raw = _filteredTransactions[index];
                        final tx = raw as Map<String, dynamic>;
                        final isIncome =
                            (tx['is_income'] as bool?) ??
                            (tx['type'] == 'income');
                        final categoryName =
                            (tx['category_name'] as String?) ?? 'Category';
                        final displayAmount =
                            (tx['display_amount'] as dynamic?) ?? '';
                        final displayDate =
                            (tx['display_date'] as dynamic?) ?? '';
                        final description =
                            (tx['description'] as dynamic?) ?? '';
                        // Debug logging for first few items
                        if (index < 3) {
                          print(
                            'Transaction $index: category=$categoryName, amount=$displayAmount, date=$displayDate, isIncome=$isIncome',
                          );
                        }

                        return Container(
                          margin: const EdgeInsets.only(bottom: 8),
                          decoration: BoxDecoration(
                            color: theme.colorScheme.surface,
                            borderRadius: BorderRadius.circular(12),
                            border: Border.all(
                              color: theme.colorScheme.outline.withValues(
                                alpha: 26,
                              ),
                            ),
                            boxShadow: [
                              BoxShadow(
                                color: theme.shadowColor.withValues(alpha: 26),
                                blurRadius: 4,
                                offset: const Offset(0, 2),
                              ),
                            ],
                          ),
                          child: ListTile(
                            contentPadding: const EdgeInsets.all(16),
                            leading: Container(
                              width: 48,
                              height: 48,
                              decoration: BoxDecoration(
                                color: isIncome
                                    ? theme.colorScheme.secondary.withValues(
                                        alpha: 26,
                                      )
                                    : theme.colorScheme.error.withValues(
                                        alpha: 26,
                                      ),
                                borderRadius: BorderRadius.circular(12),
                              ),
                              child: Icon(
                                isIncome
                                    ? Icons.arrow_upward
                                    : Icons.arrow_downward,
                                color: isIncome
                                    ? theme.colorScheme.secondary
                                    : theme.colorScheme.error,
                                size: 24,
                              ),
                            ),
                            title: Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                Expanded(
                                  child: Text(
                                    categoryName,
                                    style: theme.textTheme.titleMedium
                                        ?.copyWith(
                                          fontWeight: FontWeight.w600,
                                          color: theme.colorScheme.onSurface,
                                        ),
                                  ),
                                ),
                                Text(
                                  displayAmount,
                                  style: theme.textTheme.titleMedium?.copyWith(
                                    fontWeight: FontWeight.bold,
                                    color: isIncome
                                        ? theme.colorScheme.secondary
                                        : theme.colorScheme.error,
                                  ),
                                ),
                              ],
                            ),
                            subtitle: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                const SizedBox(height: 4),
                                Text(
                                  displayDate,
                                  style: theme.textTheme.bodySmall?.copyWith(
                                    color: theme.colorScheme.onSurface
                                        .withValues(alpha: 128),
                                  ),
                                ),
                                if (description.isNotEmpty) ...[
                                  const SizedBox(height: 2),
                                  Text(
                                    description,
                                    style: theme.textTheme.bodySmall?.copyWith(
                                      color: theme.colorScheme.onSurface
                                          .withValues(alpha: 153),
                                    ),
                                    maxLines: 2,
                                    overflow: TextOverflow.ellipsis,
                                  ),
                                ],
                              ],
                            ),
                            onTap: () {
                              // TODO: Navigate to transaction detail screen
                              _showTransactionDetails(context, tx, theme);
                            },
                          ),
                        );
                      },
                    ),
                  ),
          ),
        ],
      ),
    );
  }

  Widget _buildSummaryItem(
    String label,
    String value,
    Color color,
    ThemeData theme,
  ) {
    return Column(
      children: [
        Text(
          value,
          style: theme.textTheme.titleLarge?.copyWith(
            fontWeight: FontWeight.bold,
            color: color,
          ),
        ),
        Text(
          label,
          style: theme.textTheme.bodySmall?.copyWith(
            color: theme.colorScheme.onSurface.withValues(alpha: 128),
            fontWeight: FontWeight.w500,
          ),
        ),
      ],
    );
  }

  Widget _buildEmptyState(ThemeData theme) {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Container(
            width: 80,
            height: 80,
            decoration: BoxDecoration(
              color: theme.colorScheme.surfaceContainerHighest.withValues(
                alpha: 26,
              ),
              borderRadius: BorderRadius.circular(40),
            ),
            child: Icon(
              Icons.receipt_long,
              size: 40,
              color: theme.colorScheme.onSurface.withValues(alpha: 128),
            ),
          ),
          const SizedBox(height: 16),
          Text(
            'No transactions found',
            style: theme.textTheme.titleMedium?.copyWith(
              fontWeight: FontWeight.w600,
              color: theme.colorScheme.onSurface,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            'Try adjusting your search or filter criteria',
            style: theme.textTheme.bodyMedium?.copyWith(
              color: theme.colorScheme.onSurface.withValues(alpha: 128),
            ),
            textAlign: TextAlign.center,
          ),
        ],
      ),
    );
  }

  void _showTransactionDetails(
    BuildContext context,
    Map<String, dynamic> transaction,
    ThemeData theme,
  ) {
    final isIncome =
        (transaction['is_income'] as bool?) ??
        (transaction['type'] == 'income');
    final categoryName =
        (transaction['category_name'] as String?) ?? 'Category';
    final displayAmount = (transaction['display_amount'] as dynamic?) ?? '';
    final displayDate = (transaction['display_date'] as dynamic?) ?? '';
    final description = (transaction['description'] as dynamic?) ?? '';

    showModalBottomSheet(
      context: context,
      backgroundColor: theme.colorScheme.surface,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) => Container(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Container(
                  width: 48,
                  height: 48,
                  decoration: BoxDecoration(
                    color: isIncome
                        ? theme.colorScheme.secondary.withValues(alpha: 26)
                        : theme.colorScheme.error.withValues(alpha: 26),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Icon(
                    isIncome ? Icons.arrow_upward : Icons.arrow_downward,
                    color: isIncome
                        ? theme.colorScheme.secondary
                        : theme.colorScheme.error,
                    size: 24,
                  ),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        categoryName,
                        style: theme.textTheme.titleLarge?.copyWith(
                          fontWeight: FontWeight.bold,
                          color: theme.colorScheme.onSurface,
                        ),
                      ),
                      Text(
                        displayDate,
                        style: theme.textTheme.bodyMedium?.copyWith(
                          color: theme.colorScheme.onSurface.withValues(
                            alpha: 128,
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
                Text(
                  displayAmount,
                  style: theme.textTheme.titleLarge?.copyWith(
                    fontWeight: FontWeight.bold,
                    color: isIncome
                        ? theme.colorScheme.secondary
                        : theme.colorScheme.error,
                  ),
                ),
              ],
            ),
            if (description.isNotEmpty) ...[
              const SizedBox(height: 16),
              Text(
                'Description',
                style: theme.textTheme.titleSmall?.copyWith(
                  fontWeight: FontWeight.w600,
                  color: theme.colorScheme.onSurface,
                ),
              ),
              const SizedBox(height: 8),
              Text(
                description,
                style: theme.textTheme.bodyMedium?.copyWith(
                  color: theme.colorScheme.onSurface.withValues(alpha: 179),
                ),
              ),
            ],
            const SizedBox(height: 24),
            Row(
              children: [
                Expanded(
                  child: OutlinedButton(
                    onPressed: () => Navigator.of(context).pop(),
                    style: OutlinedButton.styleFrom(
                      padding: const EdgeInsets.symmetric(vertical: 12),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12),
                      ),
                    ),
                    child: Text(
                      'Close',
                      style: theme.textTheme.bodyMedium?.copyWith(
                        color: theme.colorScheme.primary,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: ElevatedButton(
                    onPressed: () {
                      // TODO: Implement edit transaction
                      Navigator.of(context).pop();
                    },
                    style: ElevatedButton.styleFrom(
                      padding: const EdgeInsets.symmetric(vertical: 12),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12),
                      ),
                    ),
                    child: Text(
                      'Edit',
                      style: theme.textTheme.bodyMedium?.copyWith(
                        color: theme.colorScheme.onPrimary,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildLoadingState(ThemeData theme) {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          CircularProgressIndicator(
            valueColor: AlwaysStoppedAnimation<Color>(
              theme.colorScheme.primary,
            ),
          ),
          const SizedBox(height: 16),
          Text(
            'Loading transactions...',
            style: theme.textTheme.bodyMedium?.copyWith(
              color: theme.colorScheme.onSurface.withValues(alpha: 128),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildErrorState(ThemeData theme) {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Container(
            width: 80,
            height: 80,
            decoration: BoxDecoration(
              color: theme.colorScheme.error.withValues(alpha: 26),
              borderRadius: BorderRadius.circular(40),
            ),
            child: Icon(
              Icons.error_outline,
              size: 40,
              color: theme.colorScheme.error,
            ),
          ),
          const SizedBox(height: 16),
          Text(
            'Failed to load transactions',
            style: theme.textTheme.titleMedium?.copyWith(
              fontWeight: FontWeight.w600,
              color: theme.colorScheme.onSurface,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            _errorMessage ?? 'An unexpected error occurred',
            style: theme.textTheme.bodyMedium?.copyWith(
              color: theme.colorScheme.onSurface.withValues(alpha: 128),
            ),
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: 24),
          ElevatedButton.icon(
            onPressed: _loadAllTransactions,
            icon: Icon(Icons.refresh, color: theme.colorScheme.onPrimary),
            label: Text(
              'Try Again',
              style: theme.textTheme.bodyMedium?.copyWith(
                color: theme.colorScheme.onPrimary,
                fontWeight: FontWeight.w600,
              ),
            ),
            style: ElevatedButton.styleFrom(
              padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(12),
              ),
            ),
          ),
        ],
      ),
    );
  }
}
