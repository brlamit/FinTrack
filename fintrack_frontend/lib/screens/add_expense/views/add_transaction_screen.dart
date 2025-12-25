import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:intl/intl.dart';

import '../../../services/api_service.dart';

class AddTransactionScreen extends StatefulWidget {
  const AddTransactionScreen({super.key});

  @override
  State<AddTransactionScreen> createState() => _AddTransactionScreenState();
}

class _AddTransactionScreenState extends State<AddTransactionScreen> {
  final TextEditingController _amountController = TextEditingController();
  final TextEditingController _descriptionController = TextEditingController();

  DateTime _selectedDate = DateTime.now();
  String _type = 'expense';

  List<dynamic> _categories = [];
  dynamic _selectedCategory;
  bool _loadingCategories = true;
  bool _saving = false;
  String? _error;

  XFile? _receiptImage;
  final ImagePicker _imagePicker = ImagePicker();

  @override
  void initState() {
    super.initState();
    _loadCategories();
  }

  Future<void> _loadCategories() async {
    setState(() {
      _loadingCategories = true;
      _error = null;
    });
    try {
      final cats = await ApiService.fetchCategories(type: _type);
      setState(() {
        _categories = cats;
        if (_categories.isNotEmpty) {
          _selectedCategory = _categories.first;
        }
      });
    } catch (e) {
      setState(() {
        _error = e.toString();
      });
    } finally {
      setState(() {
        _loadingCategories = false;
      });
    }
  }

  Future<void> _pickDate() async {
    final picked = await showDatePicker(
      context: context,
      initialDate: _selectedDate,
      firstDate: DateTime(2000),
      lastDate: DateTime.now(),
    );
    if (picked != null) {
      setState(() {
        _selectedDate = picked;
      });
    }
  }

  Future<void> _save() async {
    final amountText = _amountController.text.trim();
    if (_selectedCategory == null) {
      setState(() {
        _error = 'Please select a category.';
      });
      return;
    }

    double? amount;
    if (amountText.isNotEmpty) {
      amount = double.tryParse(amountText.replaceAll(',', ''));
      if (amount == null || amount <= 0) {
        setState(() {
          _error = 'Please enter a valid amount.';
        });
        return;
      }
    }

    final int? categoryId = _selectedCategory['id'] as int?;
    if (categoryId == null) {
      setState(() {
        _error = 'Invalid category selected.';
      });
      return;
    }

    setState(() {
      _saving = true;
      _error = null;
    });

    try {
      int? receiptId;

      // If user attached a receipt, upload it and try to infer amount
      if (_receiptImage != null) {
        final bytes = await _receiptImage!.readAsBytes();
        final fileName = _receiptImage!.name.isNotEmpty
            ? _receiptImage!.name
            : 'receipt_${DateTime.now().millisecondsSinceEpoch}.jpg';

        final receipt = await ApiService.uploadReceiptForTransaction(
          filename: fileName,
          bytes: bytes,
        );

        receiptId = receipt['id'] as int?;

        // If amount not provided, try to use OCR estimated_total
        if ((amount == null || amount <= 0) && receipt['parsed_data'] is Map) {
          final parsed = receipt['parsed_data'] as Map;
          final est = parsed['estimated_total'];
          if (est != null) {
            final estVal = double.tryParse(est.toString());
            if (estVal != null && estVal > 0) {
              amount = estVal;

              // Show a hint to the user about the detected amount
              if (mounted) {
                final formatted = estVal.toStringAsFixed(2);
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(
                    content: Text('Detected amount from receipt: $formatted'),
                    duration: const Duration(seconds: 3),
                  ),
                );

                // Also pre-fill the amount field so the user
                // can see and adjust it before saving.
                _amountController.text = formatted;
              }
            }
          }
        }
      }

      if (amount == null || amount <= 0) {
        setState(() {
          _saving = false;
          _error = 'Please enter a valid amount or attach a readable receipt.';
        });
        return;
      }

      final ok = await ApiService.createTransaction(
        categoryId: categoryId,
        amount: amount,
        type: _type,
        transactionDate: _selectedDate,
        description: _descriptionController.text.trim(),
        receiptId: receiptId,
      );
      if (ok && mounted) {
        Navigator.pop(context, true);
      }
    } catch (e) {
      setState(() {
        _error = e.toString();
      });
    } finally {
      if (mounted) {
        setState(() {
          _saving = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final dateLabel = DateFormat('dd/MM/yyyy').format(_selectedDate);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Add Transaction'),
        backgroundColor: theme.colorScheme.primary,
        foregroundColor: theme.colorScheme.onPrimary,
      ),
      body: Container(
        decoration: BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
            colors: theme.brightness == Brightness.light
                ? [
                    theme.colorScheme.surface,
                    theme.colorScheme.surfaceContainerLowest,
                  ]
                : [
                    theme.colorScheme.surface,
                    theme.colorScheme.surfaceContainerHighest.withOpacity(0.3),
                  ],
          ),
        ),
        child: SafeArea(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(20.0),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Header Card
                Container(
                  padding: const EdgeInsets.all(20),
                  decoration: BoxDecoration(
                    borderRadius: BorderRadius.circular(16),
                    gradient: LinearGradient(
                      begin: Alignment.topLeft,
                      end: Alignment.bottomRight,
                      colors: [
                        theme.colorScheme.primary.withOpacity(0.1),
                        theme.colorScheme.primary.withOpacity(0.05),
                      ],
                    ),
                    border: Border.all(
                      color: theme.colorScheme.primary.withOpacity(0.2),
                    ),
                  ),
                  child: Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.all(12),
                        decoration: BoxDecoration(
                          color: theme.colorScheme.primary.withOpacity(0.2),
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: Icon(
                          Icons.add_circle,
                          color: theme.colorScheme.primary,
                          size: 24,
                        ),
                      ),
                      const SizedBox(width: 16),
                      Text(
                        'Add New Transaction',
                        style: theme.textTheme.titleLarge?.copyWith(
                          fontWeight: FontWeight.bold,
                          color: theme.colorScheme.onSurface,
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 24),

                // Error Message
                if (_error != null) ...[
                  Container(
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: theme.colorScheme.error.withOpacity(0.1),
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(
                        color: theme.colorScheme.error.withOpacity(0.2),
                      ),
                    ),
                    child: Row(
                      children: [
                        Icon(Icons.error, color: theme.colorScheme.error),
                        const SizedBox(width: 12),
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

                // Description Field
                _ModernFormField(
                  label: 'Description',
                  required: true,
                  child: TextField(
                    controller: _descriptionController,
                    decoration: InputDecoration(
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(12),
                      ),
                      hintText: 'What is this transaction for?',
                      filled: true,
                      fillColor: theme.colorScheme.surface,
                    ),
                  ),
                ),

                // Type and Amount Row
                Row(
                  children: [
                    Expanded(
                      child: _ModernFormField(
                        label: 'Type',
                        required: true,
                        child: Container(
                          decoration: BoxDecoration(
                            color: theme.colorScheme.surface,
                            borderRadius: BorderRadius.circular(12),
                            border: Border.all(
                              color: theme.colorScheme.outline.withOpacity(0.3),
                            ),
                          ),
                          child: Row(
                            children: [
                              Expanded(
                                child: GestureDetector(
                                  onTap: () {
                                    setState(() {
                                      _type = 'expense';
                                    });
                                    _loadCategories();
                                  },
                                  child: Container(
                                    padding: const EdgeInsets.symmetric(
                                      vertical: 12,
                                    ),
                                    decoration: BoxDecoration(
                                      color: _type == 'expense'
                                          ? theme.colorScheme.error.withOpacity(
                                              0.1,
                                            )
                                          : Colors.transparent,
                                      borderRadius: BorderRadius.circular(12),
                                    ),
                                    child: Center(
                                      child: Text(
                                        'Expense',
                                        style: theme.textTheme.bodyMedium
                                            ?.copyWith(
                                              color: _type == 'expense'
                                                  ? theme.colorScheme.error
                                                  : theme.colorScheme.onSurface
                                                        .withOpacity(0.6),
                                              fontWeight: _type == 'expense'
                                                  ? FontWeight.bold
                                                  : FontWeight.normal,
                                            ),
                                      ),
                                    ),
                                  ),
                                ),
                              ),
                              Expanded(
                                child: GestureDetector(
                                  onTap: () {
                                    setState(() {
                                      _type = 'income';
                                    });
                                    _loadCategories();
                                  },
                                  child: Container(
                                    padding: const EdgeInsets.symmetric(
                                      vertical: 12,
                                    ),
                                    decoration: BoxDecoration(
                                      color: _type == 'income'
                                          ? theme.colorScheme.secondary
                                                .withOpacity(0.1)
                                          : Colors.transparent,
                                      borderRadius: BorderRadius.circular(12),
                                    ),
                                    child: Center(
                                      child: Text(
                                        'Income',
                                        style: theme.textTheme.bodyMedium
                                            ?.copyWith(
                                              color: _type == 'income'
                                                  ? theme.colorScheme.secondary
                                                  : theme.colorScheme.onSurface
                                                        .withOpacity(0.6),
                                              fontWeight: _type == 'income'
                                                  ? FontWeight.bold
                                                  : FontWeight.normal,
                                            ),
                                      ),
                                    ),
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                    ),
                    const SizedBox(width: 16),
                    Expanded(
                      child: _ModernFormField(
                        label: 'Amount',
                        child: TextField(
                          controller: _amountController,
                          keyboardType: const TextInputType.numberWithOptions(
                            decimal: true,
                          ),
                          decoration: InputDecoration(
                            border: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(12),
                            ),
                            hintText: '0.00',
                            prefixText: '\$',
                            filled: true,
                            fillColor: theme.colorScheme.surface,
                          ),
                        ),
                      ),
                    ),
                  ],
                ),

                // Category Field
                _ModernFormField(
                  label: 'Category',
                  required: true,
                  child: _loadingCategories
                      ? Container(
                          padding: const EdgeInsets.all(16),
                          decoration: BoxDecoration(
                            color: theme.colorScheme.surface,
                            borderRadius: BorderRadius.circular(12),
                            border: Border.all(
                              color: theme.colorScheme.outline.withOpacity(0.3),
                            ),
                          ),
                          child: const Center(
                            child: SizedBox(
                              width: 20,
                              height: 20,
                              child: CircularProgressIndicator(strokeWidth: 2),
                            ),
                          ),
                        )
                      : _categories.isEmpty
                      ? Container(
                          padding: const EdgeInsets.all(16),
                          decoration: BoxDecoration(
                            color: theme.colorScheme.surface,
                            borderRadius: BorderRadius.circular(12),
                            border: Border.all(
                              color: theme.colorScheme.outline.withOpacity(0.3),
                            ),
                          ),
                          child: Text(
                            'No categories available. Please create categories first.',
                            style: theme.textTheme.bodyMedium?.copyWith(
                              color: theme.colorScheme.onSurface.withOpacity(
                                0.6,
                              ),
                            ),
                          ),
                        )
                      : Container(
                          decoration: BoxDecoration(
                            color: theme.colorScheme.surface,
                            borderRadius: BorderRadius.circular(12),
                            border: Border.all(
                              color: theme.colorScheme.outline.withOpacity(0.3),
                            ),
                          ),
                          child: DropdownButtonFormField<dynamic>(
                            initialValue: _selectedCategory,
                            items: _categories
                                .map(
                                  (c) => DropdownMenuItem<dynamic>(
                                    value: c,
                                    child: Text(
                                      c['name']?.toString() ?? 'Category',
                                      style: theme.textTheme.bodyMedium,
                                    ),
                                  ),
                                )
                                .toList(),
                            onChanged: (val) {
                              setState(() {
                                _selectedCategory = val;
                              });
                            },
                            decoration: const InputDecoration(
                              border: InputBorder.none,
                              contentPadding: EdgeInsets.symmetric(
                                horizontal: 16,
                                vertical: 12,
                              ),
                            ),
                            icon: Icon(
                              Icons.arrow_drop_down,
                              color: theme.colorScheme.onSurface.withOpacity(
                                0.6,
                              ),
                            ),
                          ),
                        ),
                ),

                // Date Field
                _ModernFormField(
                  label: 'Date',
                  required: true,
                  child: GestureDetector(
                    onTap: _pickDate,
                    child: Container(
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        color: theme.colorScheme.surface,
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(
                          color: theme.colorScheme.outline.withOpacity(0.3),
                        ),
                      ),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Text(
                            dateLabel,
                            style: theme.textTheme.bodyMedium?.copyWith(
                              color: theme.colorScheme.onSurface,
                            ),
                          ),
                          Icon(
                            Icons.calendar_today,
                            color: theme.colorScheme.onSurface.withOpacity(0.6),
                            size: 20,
                          ),
                        ],
                      ),
                    ),
                  ),
                ),

                // Receipt Field
                _ModernFormField(
                  label: 'Receipt (optional)',
                  child: GestureDetector(
                    onTap: _saving
                        ? null
                        : () async {
                            final picked = await _imagePicker.pickImage(
                              source: ImageSource.gallery,
                            );
                            if (picked != null) {
                              setState(() {
                                _receiptImage = picked;
                              });
                            }
                          },
                    child: Container(
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        color: theme.colorScheme.surface,
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(
                          color: theme.colorScheme.outline.withOpacity(0.3),
                        ),
                      ),
                      child: Row(
                        children: [
                          Icon(
                            Icons.receipt_long,
                            color: theme.colorScheme.onSurface.withOpacity(0.6),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Text(
                              _receiptImage != null
                                  ? _receiptImage!.name
                                  : 'Choose image (bill/receipt)',
                              style: theme.textTheme.bodyMedium?.copyWith(
                                color: _receiptImage != null
                                    ? theme.colorScheme.onSurface
                                    : theme.colorScheme.onSurface.withOpacity(
                                        0.6,
                                      ),
                              ),
                            ),
                          ),
                          if (_receiptImage != null)
                            IconButton(
                              onPressed: () {
                                setState(() {
                                  _receiptImage = null;
                                });
                              },
                              icon: Icon(
                                Icons.clear,
                                color: theme.colorScheme.error,
                                size: 20,
                              ),
                            ),
                        ],
                      ),
                    ),
                  ),
                ),

                const SizedBox(height: 32),

                // Save Button
                SizedBox(
                  width: double.infinity,
                  height: 56,
                  child: ElevatedButton(
                    onPressed: _saving ? null : _save,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: theme.colorScheme.primary,
                      foregroundColor: theme.colorScheme.onPrimary,
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(16),
                      ),
                      elevation: 4,
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
                              const Icon(Icons.save),
                              const SizedBox(width: 8),
                              const Text(
                                'Add Transaction',
                                style: TextStyle(
                                  fontSize: 16,
                                  fontWeight: FontWeight.bold,
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
              style: theme.textTheme.bodyLarge?.copyWith(
                fontWeight: FontWeight.w600,
                color: theme.colorScheme.onSurface,
              ),
              children: required
                  ? [
                      TextSpan(
                        text: ' *',
                        style: TextStyle(
                          color: theme.colorScheme.error,
                          fontWeight: FontWeight.bold,
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
