import 'dart:convert';
import 'dart:io';
import 'package:supabase_flutter/supabase_flutter.dart';
import 'package:shared_preferences/shared_preferences.dart';

class ApiService {
  static SharedPreferences? _prefs;
  static Map<String, dynamic>? currentUser;
  static String? token;

  static SupabaseClient get _supabase => Supabase.instance.client;

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

  static bool get isLoggedIn => _supabase.auth.currentUser != null;

  // -------------------------
  // LOGIN
  // -------------------------
  static Future<bool> login(String emailOrUsername, String password) async {
    try {
      String loginEmail = emailOrUsername;

      // Username login support
      if (!loginEmail.contains('@')) {
        final userRecord = await _supabase
            .from('users')
            .select('email')
            .eq('username', loginEmail)
            .maybeSingle();

        if (userRecord == null) {
          throw Exception('User not found');
        }

        loginEmail = userRecord['email'];
      }

      final response = await _supabase.auth.signInWithPassword(
        email: loginEmail,
        password: password,
      );

      if (response.user == null) {
        return false;
      }

      final userId = response.user!.id;

      // Load user profile
      final userProfile = await _supabase
          .from('users')
          .select()
          .eq('id', userId)
          .maybeSingle();

      if (userProfile != null) {
        currentUser = userProfile;
      } else {
        // Create profile if missing
        final newUser = {
          'id': userId,
          'name': response.user!.userMetadata?['name'] ?? 'User',
          'email': response.user!.email,
          'username': response.user!.userMetadata?['username'] ??
              response.user!.email!.split('@').first,
        };

        await _supabase.from('users').insert(newUser);
        currentUser = newUser;
      }

      // Save locally
      await _prefs?.setString('user', jsonEncode(currentUser));
      token = response.session?.accessToken;
      if (token != null) {
        await _prefs?.setString('token', token!);
      }

      return true;
    } catch (e) {
      throw Exception('Login failed: $e');
    }
  }

  // -------------------------
  // REGISTER
  // -------------------------
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

      if (response.user == null) return false;

      final profileData = {
        'id': response.user!.id,
        'name': payload['name'],
        'email': payload['email'],
        'username': payload['username'],
      };

      // Safe upsert
      await _supabase.from('users').upsert(profileData);

      currentUser = profileData;
      await _prefs?.setString('user', jsonEncode(profileData));

      return true;
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

        await _supabase.storage
            .from('receipts')
            .upload(receiptPath, imageFile);
      }

      // Insert transaction
      final transactionData = {
        'user_id': _supabase.auth.currentUser!.id,
        'group_id': groupId,
        'amount': double.tryParse(amount) ?? 0.0,
        'description': description,
        'type': 'expense',
        'split_meta': {
          'splits': splits,
          'split_type': splitType,
        },
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
            .update({'limit_amount': newLimit}).eq('id', budget['id']);
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
    await _supabase.auth.signOut();
    currentUser = null;
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
