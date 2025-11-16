import 'dart:convert';
import 'dart:io';
import 'package:supabase_flutter/supabase_flutter.dart';
import 'package:shared_preferences/shared_preferences.dart';

class ApiService {
  static SharedPreferences? _prefs;
  static Map<String, dynamic>? currentUser;
  static String? token; // Keep for backward compatibility if needed

  static SupabaseClient get _supabase => Supabase.instance.client;

  static Future<void> init() async {
    _prefs = await SharedPreferences.getInstance();
    token = _prefs?.getString('token');
    final u = _prefs?.getString('user');
    if (u != null && u.isNotEmpty) {
      try {
        currentUser = jsonDecode(u) as Map<String, dynamic>?;
      } catch (_) {
        currentUser = null;
      }
    }
  }

  static bool get isLoggedIn => _supabase.auth.currentUser != null;

  /// POST /api/login with { username, password }
  /// expects { token: string, user: {...} }
  static Future<bool> login(String login, String password) async {
    try {
      final response = await _supabase.auth.signInWithPassword(
        email: login,
        password: password,
      );
      if (response.user != null) {
        // Get user profile from database, create if doesn't exist
        try {
          final userData = await _supabase
              .from('users')
              .select()
              .eq('id', response.user!.id)
              .single();
          currentUser = userData;
        } catch (e) {
          // Profile doesn't exist, create it
          final userData = {
            'id': response.user!.id,
            'name': response.user!.userMetadata?['name'] ?? 'User',
            'email': response.user!.email!,
            'username': response.user!.userMetadata?['username'] ??
                response.user!.email!.split('@').first,
          };
          await _supabase.from('users').insert(userData);
          currentUser = userData;
        }
        await _prefs?.setString('user', jsonEncode(currentUser));
        return true;
      }
      return false;
    } catch (e) {
      throw Exception('Login failed: $e');
    }
  }

  /// Register a new user. Expects fields: name, email, username, password
  /// Returns true on success (201 or 200).
  static Future<bool> register(Map<String, String> payload) async {
    try {
      final response = await _supabase.auth.signUp(
        email: payload['email']!,
        password: payload['password']!,
        data: {
          'name': payload['name'],
          'username': payload['username'],
        },
      );
      if (response.user != null) {
        // User profile will be created automatically by database trigger
        // No need to manually insert into users table
        return true;
      }
      return false;
    } catch (e) {
      throw Exception('Register failed: $e');
    }
  }

  /// Add expense to a group, with optional image and split data.
  /// `splits` should be a List of maps: [{ 'user_id': id, 'amount': '12.34' }, ...]
  static Future<bool> addExpense(int groupId, String amount, String description,
      List<Map<String, dynamic>> splits,
      {File? imageFile, String splitType = 'custom'}) async {
    try {
      String? receiptPath;
      if (imageFile != null && await imageFile.exists()) {
        final fileName =
            '${DateTime.now().millisecondsSinceEpoch}_${imageFile.path.split(Platform.pathSeparator).last}';
        receiptPath = 'receipts/$fileName';
        await _supabase.storage.from('receipts').upload(receiptPath, imageFile);
      }

      // Insert transaction
      final transactionData = {
        'user_id': _supabase.auth.currentUser!.id,
        'group_id': groupId,
        'amount': double.parse(amount),
        'description': description,
        'type': 'expense',
        'split_meta': {'splits': splits, 'split_type': splitType},
        'date': DateTime.now().toIso8601String().split('T').first,
      };

      final transactionResponse = await _supabase
          .from('transactions')
          .insert(transactionData)
          .select()
          .single();

      // If receipt, insert receipt record
      if (receiptPath != null) {
        await _supabase.from('receipts').insert({
          'transaction_id': transactionResponse['id'],
          'user_id': _supabase.auth.currentUser!.id,
          'storage_path': receiptPath,
          'filename': imageFile!.path.split(Platform.pathSeparator).last,
          'mime': 'image/jpeg', // assume, or detect
          'size': await imageFile.length(),
        });
      }

      // Deduct from group budget if exists
      // Assuming budgets table has owner_type 'group', owner_id groupId
      final budgets = await _supabase
          .from('budgets')
          .select()
          .eq('owner_type', 'group')
          .eq('owner_id', groupId);
      if (budgets.isNotEmpty) {
        // For simplicity, assume one budget, deduct amount
        final budget = budgets.first;
        final newLimit = budget['limit_amount'] - double.parse(amount);
        await _supabase
            .from('budgets')
            .update({'limit_amount': newLimit}).eq('id', budget['id']);
      }

      return true;
    } catch (e) {
      throw Exception('Add expense failed: $e');
    }
  }

  static Future<void> logout() async {
    await _supabase.auth.signOut();
    currentUser = null;
    await _prefs?.remove('user');
  }

  static Future<List<dynamic>> fetchGroups() async {
    try {
      final userId = _supabase.auth.currentUser!.id;
      // Groups where user is owner
      final ownedGroups =
          await _supabase.from('groups').select().eq('owner_id', userId);

      // Groups where user is member
      final memberGroups = await _supabase
          .from('group_members')
          .select('groups(*)')
          .eq('user_id', userId);

      final groups = [
        ...ownedGroups,
        ...memberGroups.map((m) => m['groups']).where((g) => g != null)
      ];
      return groups;
    } catch (e) {
      throw Exception('Failed to load groups: $e');
    }
  }

  static Future<Map<String, dynamic>> fetchGroup(int groupId) async {
    try {
      final group =
          await _supabase.from('groups').select().eq('id', groupId).single();

      final members = await _supabase
          .from('group_members')
          .select('users(*)')
          .eq('group_id', groupId);

      final transactions = await _supabase
          .from('transactions')
          .select()
          .eq('group_id', groupId)
          .order('created_at', ascending: false);

      return {
        ...group,
        'members': members.map((m) => m['users']).toList(),
        'transactions': transactions,
      };
    } catch (e) {
      throw Exception('Failed to load group: $e');
    }
  }

  static Future<bool> removeMember(int groupId, int memberId) async {
    try {
      await _supabase
          .from('group_members')
          .delete()
          .eq('group_id', groupId)
          .eq('user_id', memberId);
      return true;
    } catch (e) {
      throw Exception('Failed to remove member: $e');
    }
  }
}
