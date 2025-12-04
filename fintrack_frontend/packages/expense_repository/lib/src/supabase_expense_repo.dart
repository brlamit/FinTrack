import 'package:expense_repository/expense_repository.dart';
import 'package:supabase_flutter/supabase_flutter.dart';

class SupabaseExpenseRepo implements ExpenseRepository {
  final SupabaseClient supabase = Supabase.instance.client;

  @override
  Future<List<Expense>> getExpenses() async {
    try {
      final response = await supabase
          .from('expenses')
          .select('*')
          .order('created_at', ascending: false);

      return response.map<Expense>((e) => Expense.fromEntity(ExpenseEntity.fromDocument(e))).toList();
    } catch (e) {
      throw Exception("Failed to fetch expenses: $e");
    }
  }

  @override
  Future<void> createExpense(Expense expense) async {
    try {
      await supabase.from('expenses').insert(expense.toEntity());
    } catch (e) {
      throw Exception("Failed to create expense: $e");
    }
  }

  Future<List<Category>> getCategories() async {
    try {
      final response = await supabase
          .from('categories')
          .select('*')
          .order('name', ascending: true);

      return response.map<Category>((e) => Category.fromEntity(CategoryEntity.fromDocument(e))).toList();
    } catch (e) {
      throw Exception("Failed to fetch categories: $e");
    }
  }

  @override
  Future<void> createCategory(Category category) async {
    try {
      await supabase.from('categories').insert(category.toEntity());
    } catch (e) {
      throw Exception("Failed to create category: $e");
    }
  }

  @override
  Future<List<Category>> getCategory() async {
    try {
      final response = await supabase
          .from('categories')
          .select('*')
          .order('name', ascending: true);

      return response.map<Category>((e) => Category.fromEntity(CategoryEntity.fromDocument(e))).toList();
    } catch (e) {
      throw Exception("Failed to fetch category: $e");
    }
  }
}
