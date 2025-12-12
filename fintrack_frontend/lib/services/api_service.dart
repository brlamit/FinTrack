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

  // Optional override for backend base (useful for testing on physical devices)
  // Example: ApiService.debugBackendOverride = 'http://192.168.1.42:8000/api';
  static String? debugBackendOverride;

  // Backend API base (select per platform). Examples:
  // - Web: http://localhost:8000/api
  // - Android emulator (Android Studio): http://10.0.2.2:8000/api
  // - Genymotion: http://10.0.3.2:8000/api
  // - iOS simulator: http://127.0.0.1:8000/api (on macOS)
  static String get backendBaseUrl {
    if (debugBackendOverride != null && debugBackendOverride!.isNotEmpty) {
      return debugBackendOverride!;
    }

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

  // Splash Screen
  static Future<bool> splash() async {
    // Just a placeholder for any startup logic if needed

    await Future.delayed(const Duration(seconds: 2));

    return true;
  }

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
        body: jsonEncode({'email': emailOrUsername, 'password': password}),
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

      // Handle validation or auth errors — try to extract structured error
      String errMsg = 'Login failed: HTTP ${resp.statusCode}';
      if (resp.body.isNotEmpty) {
        try {
          final parsed = jsonDecode(resp.body);
          if (parsed is Map && parsed['error'] != null) {
            final e = parsed['error'];
            if (e is String) {
              errMsg = '$errMsg - $e';
            } else if (e is Map && e['message'] != null)
              errMsg = '$errMsg - ${e['message']}';
            else
              errMsg = '$errMsg - ${resp.body}';
          } else if (parsed is Map && parsed['message'] != null) {
            errMsg = '$errMsg - ${parsed['message']}';
          } else {
            errMsg = '$errMsg - ${resp.body}';
          }
        } catch (_) {
          errMsg = '$errMsg - ${resp.body}';
        }
      }

      throw Exception(errMsg);
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
  // OTP VERIFY / RESEND (API)
  // -------------------------
  static Future<bool> verifyOtp(String email, String code) async {
    try {
      final uri = Uri.parse('$backendBaseUrl/auth/otp/verify');
      final resp = await http.post(
        uri,
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'email': email, 'code': code}),
      );

      if (resp.statusCode == 200 || resp.statusCode == 201) {
        // Optionally backend may return user/token after verification
        if (resp.body.isNotEmpty) {
          try {
            final body = jsonDecode(resp.body);
            if (body['token'] != null) {
              token = body['token'] as String?;
              if (token != null) await _prefs?.setString('token', token!);
            }

            if (body['user'] != null) {
              currentUser = Map<String, dynamic>.from(body['user']);
              await _prefs?.setString('user', jsonEncode(currentUser));
            }
          } catch (_) {}
        }

        return true;
      }

      final err = resp.body.isNotEmpty ? resp.body : 'OTP verify failed';
      throw Exception('OTP verify failed: HTTP ${resp.statusCode} - $err');
    } catch (e) {
      throw Exception('OTP verify failed: $e');
    }
  }

  static Future<bool> resendOtp(String email) async {
    try {
      final uri = Uri.parse('$backendBaseUrl/auth/otp/resend');
      final resp = await http.post(
        uri,
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'email': email}),
      );

      if (resp.statusCode == 200 || resp.statusCode == 201) return true;

      final err = resp.body.isNotEmpty ? resp.body : 'Resend OTP failed';
      throw Exception('Resend OTP failed: HTTP ${resp.statusCode} - $err');
    } catch (e) {
      throw Exception('Resend OTP failed: $e');
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
      final uri = Uri.parse('$backendBaseUrl/transaction/create');
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
        final uri = Uri.parse('$backendBaseUrl/auth/logout-all');
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
  // DASHBOARD (aggregate data used by web dashboard)
  // -------------------------
  static Future<Map<String, dynamic>> fetchDashboard() async {
    try {
      final headers = <String, String>{'Content-Type': 'application/json'};
      if (token != null && token!.isNotEmpty) {
        headers['Authorization'] = 'Bearer $token';
      }

      // Fetch statistics/totals
      final statsResp = await http.get(
        Uri.parse('$backendBaseUrl/transactions/statistics'),
        headers: headers,
      );

      Map<String, dynamic>? stats;
      if (statsResp.statusCode == 200 && statsResp.body.isNotEmpty) {
        final decoded = jsonDecode(statsResp.body);
        if (decoded is Map && decoded['data'] != null) {
          stats = Map<String, dynamic>.from(decoded['data']);
        }
      }
      // Fetch insights
      final insightsResp = await http.get(
        Uri.parse('$backendBaseUrl/insights'),
        headers: headers,
      );
      List<dynamic>? insights;
      if (insightsResp.statusCode == 200 && insightsResp.body.isNotEmpty) {
        final decoded = jsonDecode(insightsResp.body);
        if (decoded is Map && decoded['data'] != null) {
          insights = List<dynamic>.from(decoded['data']);
        }
      }

      // Fetch recent transactions (server returns newest first)
      final txResp = await http.get(
        Uri.parse('$backendBaseUrl/transactions'),
        headers: headers,
      );
      List<dynamic>? transactions;
      if (txResp.statusCode == 200 && txResp.body.isNotEmpty) {
        final decoded = jsonDecode(txResp.body);
        if (decoded is Map && decoded['data'] != null) {
          final page = decoded['data'];
          if (page is Map && page['data'] != null) {
            transactions = List<dynamic>.from(page['data']);
          }
        }
      }

      // Fetch budgets
      final budResp = await http.get(
        Uri.parse('$backendBaseUrl/budgets'),
        headers: headers,
      );
      List<dynamic>? budgets;
      if (budResp.statusCode == 200 && budResp.body.isNotEmpty) {
        final decoded = jsonDecode(budResp.body);
        if (decoded is Map && decoded['data'] != null) {
          budgets = List<dynamic>.from(decoded['data']);
        }
      }

      return {
        'statistics': stats,
        'insights': insights,
        'transactions': transactions,
        'budgets': budgets,
      };
    } catch (e) {
      throw Exception('Failed to fetch dashboard: $e');
    }
  }

  // -------------------------
  // CATEGORIES (from backend)
  // -------------------------
  static Future<List<dynamic>> fetchCategories({String? type}) async {
    try {
      final headers = <String, String>{'Content-Type': 'application/json'};
      if (token != null && token!.isNotEmpty) {
        headers['Authorization'] = 'Bearer $token';
      }

      Uri uri = Uri.parse('$backendBaseUrl/categories');
      if (type != null && type.isNotEmpty) {
        uri = uri.replace(queryParameters: {'type': type});
      }

      final resp = await http.get(uri, headers: headers);

      if (resp.statusCode == 200 && resp.body.isNotEmpty) {
        final decoded = jsonDecode(resp.body);
        if (decoded is Map && decoded['data'] != null) {
          return List<dynamic>.from(decoded['data']);
        }
      }

      final err = resp.body.isNotEmpty ? resp.body : 'Fetch categories failed';
      throw Exception(
        'Fetch categories failed: HTTP ${resp.statusCode} - $err',
      );
    } catch (e) {
      throw Exception('Fetch categories failed: $e');
    }
  }

  // -------------------------
  // CREATE TRANSACTION (backend)
  // -------------------------
  static Future<bool> createTransaction({
    required int categoryId,
    required double amount,
    required String type,
    required DateTime transactionDate,
    String? description,
  }) async {
    try {
      final headers = <String, String>{'Content-Type': 'application/json'};
      if (token != null && token!.isNotEmpty) {
        headers['Authorization'] = 'Bearer $token';
      }

      final body = <String, dynamic>{
        'category_id': categoryId,
        'amount': amount,
        'transaction_date': transactionDate.toIso8601String().split('T').first,
        'type': type,
      };

      if (description != null && description.isNotEmpty) {
        body['description'] = description;
      }

      final resp = await http.post(
        Uri.parse('$backendBaseUrl/transactions'),
        headers: headers,
        body: jsonEncode(body),
      );

      if (resp.statusCode == 201 || resp.statusCode == 200) {
        return true;
      }

      final err = resp.body.isNotEmpty
          ? resp.body
          : 'Create transaction failed';
      throw Exception(
        'Create transaction failed: HTTP ${resp.statusCode} - $err',
      );
    } catch (e) {
      throw Exception('Create transaction failed: $e');
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
