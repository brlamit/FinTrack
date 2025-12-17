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
                    content: Text(
                      'Detected amount from receipt: $formatted',
                    ),
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
          _error =
              'Please enter a valid amount or attach a readable receipt.';
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
    final dateLabel = DateFormat('dd/MM/yyyy').format(_selectedDate);

    return Scaffold(
      appBar: AppBar(title: const Text('Add Transaction')),
      body: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            if (_error != null) ...[
              Text(_error!, style: const TextStyle(color: Colors.red)),
              const SizedBox(height: 8),
            ],
            const Text('Amount', style: TextStyle(fontWeight: FontWeight.w600)),
            const SizedBox(height: 8),
            TextField(
              controller: _amountController,
              keyboardType: const TextInputType.numberWithOptions(
                decimal: true,
              ),
              decoration: const InputDecoration(
                border: OutlineInputBorder(),
                hintText: 'Enter amount',
              ),
            ),
            const SizedBox(height: 16),
            const Text(
              'Receipt (optional)',
              style: TextStyle(fontWeight: FontWeight.w600),
            ),
            const SizedBox(height: 8),
            OutlinedButton.icon(
              onPressed: _saving
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
              icon: const Icon(Icons.receipt_long),
              label: Text(
                _receiptImage != null
                    ? _receiptImage!.name
                    : 'Choose image (bill/receipt)',
              ),
            ),
            const SizedBox(height: 16),
            const Text('Type', style: TextStyle(fontWeight: FontWeight.w600)),
            const SizedBox(height: 8),
            Row(
              children: [
                ChoiceChip(
                  label: const Text('Expense'),
                  selected: _type == 'expense',
                  onSelected: (v) {
                    if (!v) return;
                    setState(() {
                      _type = 'expense';
                    });
                    _loadCategories();
                  },
                ),
                const SizedBox(width: 8),
                ChoiceChip(
                  label: const Text('Income'),
                  selected: _type == 'income',
                  onSelected: (v) {
                    if (!v) return;
                    setState(() {
                      _type = 'income';
                    });
                    _loadCategories();
                  },
                ),
              ],
            ),
            const SizedBox(height: 16),
            const Text(
              'Category',
              style: TextStyle(fontWeight: FontWeight.w600),
            ),
            const SizedBox(height: 8),
            if (_loadingCategories)
              const Center(child: CircularProgressIndicator())
            else if (_categories.isEmpty)
              const Text('No categories available.')
            else
              DropdownButtonFormField<dynamic>(
                value: _selectedCategory,
                items: _categories
                    .map(
                      (c) => DropdownMenuItem<dynamic>(
                        value: c,
                        child: Text(c['name']?.toString() ?? 'Category'),
                      ),
                    )
                    .toList(),
                onChanged: (val) {
                  setState(() {
                    _selectedCategory = val;
                  });
                },
                decoration: const InputDecoration(border: OutlineInputBorder()),
              ),
            const SizedBox(height: 16),
            const Text('Date', style: TextStyle(fontWeight: FontWeight.w600)),
            const SizedBox(height: 8),
            InkWell(
              onTap: _pickDate,
              child: InputDecorator(
                decoration: const InputDecoration(border: OutlineInputBorder()),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(dateLabel),
                    const Icon(Icons.calendar_today, size: 18),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 16),
            const Text(
              'Description (optional)',
              style: TextStyle(fontWeight: FontWeight.w600),
            ),
            const SizedBox(height: 8),
            TextField(
              controller: _descriptionController,
              maxLines: 2,
              decoration: const InputDecoration(
                border: OutlineInputBorder(),
                hintText: 'What is this for?',
              ),
            ),
            const Spacer(),
            SizedBox(
              width: double.infinity,
              height: 48,
              child: ElevatedButton(
                onPressed: _saving ? null : _save,
                child: _saving
                    ? const SizedBox(
                        width: 20,
                        height: 20,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      )
                    : const Text('Save Transaction'),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
