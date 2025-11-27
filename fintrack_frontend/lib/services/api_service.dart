import 'dart:convert';
import 'dart:io';
import 'package:flutter/foundation.dart' show kIsWeb;
import 'package:http/http.dart' as http;
import 'package:supabase_flutter/supabase_flutter.dart';
import 'package:shared_preferences/shared_preferences.dart';

class ApiService {
  static SharedPreferences? _prefs;
  static Map<String, dynamic>? currentUser;
  static String? token;

  static SupabaseClient get _supabase => Supabase.instance.client;

  // Backend API base (select per platform). Examples:
  // - Web: http://localhost:8000/api
  // - Android emulator (Android Studio): http://10.0.2.2:8000/api
  // - Genymotion: http://10.0.3.2:8000/api
  // - iOS simulator: http://127.0.0.1:8000/api (on macOS)
  static String get backendBaseUrl {
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

  static Future<void> init() async {
    _prefs = await SharedPreferences.getInstance();

    token = _prefs?.getString('token');

    // Restore user
    final u = _prefs?.getString('user');
    if (u != null && u.isNotEmpty) {
      try {
        currentUser = jsonDecode(u);
      } catch (_) {
        currentUser = null;
      }
    }
  }

  // Consider user logged in if we have a stored token or currentUser
  static bool get isLoggedIn =>
      (token != null && token!.isNotEmpty) || currentUser != null;

  // -------------------------
  // LOGIN
  // -------------------------
  static Future<bool> login(String emailOrUsername, String password) async {
    try {
      // Call backend login endpoint (accepts `username` which can be email)
      final uri = Uri.parse('$backendBaseUrl/auth/login');
      final resp = await http.post(
        uri,
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'username': emailOrUsername, 'password': password}),
      );

      if (resp.statusCode == 200 || resp.statusCode == 201) {
        final body = jsonDecode(resp.body);
        // Backend returns { user: {...}, token: '...' }
        if (body['user'] != null) {
          currentUser = Map<String, dynamic>.from(body['user']);
        }

        token = body['token'] as String?;
        if (token != null) {
          await _prefs?.setString('token', token!);
        }

        if (currentUser != null) {
          await _prefs?.setString('user', jsonEncode(currentUser));
        }

        return true;
      }

      // Handle validation or auth errors
      final err = resp.body.isNotEmpty ? resp.body : 'Login failed';
      throw Exception('Login failed: HTTP ${resp.statusCode} - $err');
    } catch (e) {
      throw Exception('Login failed: $e');
    }
  }

  // -------------------------
  // REGISTER
  // -------------------------
  static Future<bool> register(Map<String, String> payload) async {
    try {
      final uri = Uri.parse('$backendBaseUrl/auth/register');
      final resp = await http.post(
        uri,
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode(payload),
      );

      if (resp.statusCode == 200 || resp.statusCode == 201) {
        final body = jsonDecode(resp.body);
        if (body['user'] != null) {
          currentUser = Map<String, dynamic>.from(body['user']);
        }

        token = body['token'] as String?;
        if (token != null) {
          await _prefs?.setString('token', token!);
        }

        if (currentUser != null) {
          await _prefs?.setString('user', jsonEncode(currentUser));
        }

        return true;
      }

      final err = resp.body.isNotEmpty ? resp.body : 'Register failed';
      throw Exception('Register failed: HTTP ${resp.statusCode} - $err');
    } catch (e) {
      throw Exception('Register failed: $e');
    }
  }

  // -------------------------
  // ADD EXPENSE
  // -------------------------
  static Future<bool> addExpense(
    int groupId,
    String amount,
    String description,
    List<Map<String, dynamic>> splits, {
    File? imageFile,
    String splitType = 'custom',
  }) async {
    try {
      String? receiptPath;

      // Upload image (if exists)
      if (imageFile != null && await imageFile.exists()) {
        final fileName =
            '${DateTime.now().millisecondsSinceEpoch}_${imageFile.path.split('/').last}';

        receiptPath = 'receipts/$fileName';

        await _supabase.storage.from('receipts').upload(receiptPath, imageFile);
      }

      // Insert transaction
      final transactionData = {
        'user_id': _supabase.auth.currentUser!.id,
        'group_id': groupId,
        'amount': double.tryParse(amount) ?? 0.0,
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

      // Insert receipt record
      if (receiptPath != null) {
        await _supabase.from('receipts').insert({
          'transaction_id': transactionResponse['id'],
          'user_id': _supabase.auth.currentUser!.id,
          'storage_path': receiptPath,
          'filename': imageFile!.path.split('/').last,
          'mime': 'image/jpeg',
          'size': await imageFile.length(),
        });
      }

      // Budget deduction
      final budgets = await _supabase
          .from('budgets')
          .select()
          .eq('owner_type', 'group')
          .eq('owner_id', groupId);

      if (budgets.isNotEmpty) {
        final budget = budgets.first;
        final newLimit =
            (budget['limit_amount'] as num) - (double.tryParse(amount) ?? 0.0);

        await _supabase
            .from('budgets')
            .update({'limit_amount': newLimit})
            .eq('id', budget['id']);
      }

      return true;
    } catch (e) {
      throw Exception('Add expense failed: $e');
    }
  }

  // -------------------------
  // LOGOUT
  // -------------------------
  static Future<void> logout() async {
    try {
      if (token != null) {
        final uri = Uri.parse('$backendBaseUrl/auth/logout');
        await http.post(
          uri,
          headers: {
            'Content-Type': 'application/json',
            'Authorization': 'Bearer $token',
          },
        );
      }
    } catch (_) {}

    // Clear local state
    currentUser = null;
    token = null;
    await _prefs?.remove('user');
    await _prefs?.remove('token');
  }

  // -------------------------
  // FETCH GROUPS
  // -------------------------
  static Future<List<dynamic>> fetchGroups() async {
    try {
      final userId = _supabase.auth.currentUser!.id;

      final owned = await _supabase
          .from('groups')
          .select()
          .eq('owner_id', userId);

      final member = await _supabase
          .from('group_members')
          .select('groups(*)')
          .eq('user_id', userId);

      return [
        ...owned,
        ...member.map((m) => m['groups']).where((g) => g != null),
      ];
    } catch (e) {
      throw Exception('Failed to load groups: $e');
    }
  }

  // -------------------------
  // FETCH SINGLE GROUP
  // -------------------------
  static Future<Map<String, dynamic>> fetchGroup(int groupId) async {
    try {
      final group = await _supabase
          .from('groups')
          .select()
          .eq('id', groupId)
          .single();

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

  // -------------------------
  // REMOVE MEMBER
  // -------------------------
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
