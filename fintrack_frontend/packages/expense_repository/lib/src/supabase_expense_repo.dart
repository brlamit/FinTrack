import 'package:expense_repository/expense_repository.dart';
import 'package:supabase_flutter/supabase_flutter.dart';
import 'package:flutter/foundation.dart' show kIsWeb;
import 'dart:io';
// import 'package:fintrack_frontend/services/api_service.dart';
class SupabaseExpenseRepo implements ExpenseRepository {
  final SupabaseClient supabase = Supabase.instance.client;

  static String get backendBaseUrl {
    // if (debugBackendOverride != null && debugBackendOverride!.isNotEmpty) {
    //   return debugBackendOverride!;
    // }

    if (kIsWeb) return 'http://localhost:8000/api';
    try {
      if (Platform.isAndroid) return 'http://10.0.2.2:8000/api';
      if (Platform.isIOS) return 'http://127.0.0.1:8000/api';
      // Desktop (Windows/macOS/Linux) and other platforms — assume localhost
      return 'http://localhost:8000/api';
    } catch (_) {
      // If Platform isn't available for some reason, fall back to localhost
      return 'http://localhost:8000/api';
    }
  }

  @override
  Future<List<Expense>> getExpenses() async {
    try {
       final uri = Uri.parse('$backendBaseUrl/transaction/create');
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
