import 'package:expense_repository/expense_repository.dart';

class InMemoryExpenseRepo implements ExpenseRepository {
  final List<Category> _categories = [];
  final List<Expense> _expenses = [];

  @override
  Future<void> createCategory(Category category) async {
    _categories.removeWhere((c) => c.categoryId == category.categoryId);
    _categories.add(category);
  }

  @override
  Future<List<Category>> getCategory() async {
    // Return a copy to avoid external mutation
    return List.unmodifiable(_categories);
  }

  @override
  Future<void> createExpense(Expense expense) async {
    _expenses.removeWhere((e) => e.expenseId == expense.expenseId);
    _expenses.add(expense);

    // Update category totalExpenses if category matches
    final idx = _categories.indexWhere(
      (c) => c.categoryId == expense.category.categoryId,
    );
    if (idx >= 0) {
      final cat = _categories[idx];
      cat.totalExpenses = (cat.totalExpenses) + expense.amount;
    }
  }

  @override
  Future<List<Expense>> getExpenses() async {
    return List.unmodifiable(_expenses);
  }
}
