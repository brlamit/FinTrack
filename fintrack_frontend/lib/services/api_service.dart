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

  // Helper: safely convert dynamic backend values (num/string) to double
  static double _parseDouble(dynamic value) {
    if (value is num) return value.toDouble();
    if (value is String) {
      // Remove currency symbols and grouping chars if present
      final cleaned = value.replaceAll(RegExp(r'[^0-9.\-]'), '');
      return double.tryParse(cleaned) ?? 0.0;
    }
    return 0.0;
  }

  // Helper: format date from backend to display format
  static dynamic _formatDate(dynamic dateValue) {
    if (dateValue is String) {
      try {
        final parsed = DateTime.parse(dateValue);
        return '${parsed.day.toString().padLeft(2, '0')}/${parsed.month.toString().padLeft(2, '0')}/${parsed.year}';
      } catch (e) {
        return dateValue; // fallback to original if parsing fails
      }
    }
    return dateValue?.toString() ?? '';
  }

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
  // CURRENT USER / PROFILE
  // -------------------------
  static Future<Map<String, dynamic>> fetchCurrentUser() async {
    try {
      final headers = <String, String>{'Accept': 'application/json'};
      if (token != null && token!.isNotEmpty) {
        headers['Authorization'] = 'Bearer $token';
      }

      final resp = await http.get(
        Uri.parse('$backendBaseUrl/me'),
        headers: headers,
      );

      if (resp.statusCode == 200 && resp.body.isNotEmpty) {
        final decoded = jsonDecode(resp.body);
        if (decoded is Map<String, dynamic>) {
          // keep local cache in sync
          currentUser = decoded;
          await _prefs?.setString('user', jsonEncode(currentUser));
          return decoded;
        }
      }

      final err = resp.body.isNotEmpty ? resp.body : 'Fetch profile failed';
      throw Exception('Fetch profile failed: HTTP ${resp.statusCode} - $err');
    } catch (e) {
      throw Exception('Fetch profile failed: $e');
    }
  }

  static Future<String> uploadAvatar({
    required String filename,
    required List<int> bytes,
  }) async {
    try {
      final uri = Uri.parse('$backendBaseUrl/profile/avatar');

      final headers = <String, String>{'Accept': 'application/json'};
      if (token != null && token!.isNotEmpty) {
        headers['Authorization'] = 'Bearer $token';
      }

      final request = http.MultipartRequest('POST', uri);
      request.headers.addAll(headers);

      request.files.add(
        http.MultipartFile.fromBytes('avatar', bytes, filename: filename),
      );

      final streamed = await request.send();
      final resp = await http.Response.fromStream(streamed);

      if (resp.statusCode != 200 && resp.statusCode != 201) {
        final err = resp.body.isNotEmpty ? resp.body : 'Upload avatar failed';
        throw Exception('Upload avatar failed: HTTP ${resp.statusCode} - $err');
      }

      if (resp.body.isEmpty) {
        throw Exception('Upload avatar failed: empty response');
      }

      final decoded = jsonDecode(resp.body);
      if (decoded is Map<String, dynamic>) {
        final direct = decoded['avatar_url'];
        if (direct is String && direct.isNotEmpty) {
          // update cached user avatar so main screen/header reflects change
          if (currentUser != null) {
            currentUser = Map<String, dynamic>.from(currentUser!);
            currentUser!['avatar'] = direct;
            await _prefs?.setString('user', jsonEncode(currentUser));
          }
          return direct;
        }

        final data = decoded['data'];
        if (data is Map) {
          final fromData = data['avatar_url'];
          if (fromData is String && fromData.isNotEmpty) {
            if (currentUser != null) {
              currentUser = Map<String, dynamic>.from(currentUser!);
              currentUser!['avatar'] = fromData;
              await _prefs?.setString('user', jsonEncode(currentUser));
            }
            return fromData;
          }
        }
      }

      throw Exception('Upload avatar failed: unexpected response ${resp.body}');
    } catch (e) {
      throw Exception('Upload avatar failed: $e');
    }
  }

  /// Combined profile save (name/phone + optional avatar) for mobile
  /// Calls /api/profile/edit with multipart/form-data
  static Future<Map<String, dynamic>> saveProfileWithAvatar({
    String? name,
    String? phone,
    List<int>? avatarBytes,
    String? avatarFilename,
  }) async {
    try {
      final uri = Uri.parse('$backendBaseUrl/profile/edit');

      final headers = <String, String>{'Accept': 'application/json'};
      if (token != null && token!.isNotEmpty) {
        headers['Authorization'] = 'Bearer $token';
      }

      final request = http.MultipartRequest('POST', uri);
      request.headers.addAll(headers);

      if (name != null) request.fields['name'] = name;
      if (phone != null) request.fields['phone'] = phone;

      if (avatarBytes != null) {
        final fileName =
            avatarFilename ??
            'avatar_${DateTime.now().millisecondsSinceEpoch}.jpg';
        request.files.add(
          http.MultipartFile.fromBytes(
            'avatar',
            avatarBytes,
            filename: fileName,
          ),
        );
      }

      final streamed = await request.send();
      final resp = await http.Response.fromStream(streamed);

      if (resp.statusCode != 200 && resp.statusCode != 201) {
        final err = resp.body.isNotEmpty ? resp.body : 'Save profile failed';
        throw Exception('Save profile failed: HTTP ${resp.statusCode} - $err');
      }

      if (resp.body.isEmpty) {
        throw Exception('Save profile failed: empty response');
      }

      final decoded = jsonDecode(resp.body);
      if (decoded is! Map<String, dynamic>) {
        throw Exception(
          'Save profile failed: unexpected response ${resp.body}',
        );
      }

      // Normalize and cache user
      Map<String, dynamic>? user;
      final rawUser = decoded['user'];
      if (rawUser is Map) {
        user = Map<String, dynamic>.from(rawUser as Map);
      } else if (decoded['id'] != null) {
        // Fallback: response itself is the user object
        user = decoded;
      }

      if (user != null) {
        currentUser = user;
        await _prefs?.setString('user', jsonEncode(currentUser));
      }

      // If avatar_url present, keep cached avatar in sync
      final avatarUrl = decoded['avatar_url'];
      if (avatarUrl is String && avatarUrl.isNotEmpty) {
        if (currentUser != null) {
          currentUser = Map<String, dynamic>.from(currentUser!);
          currentUser!['avatar'] = avatarUrl;
          await _prefs?.setString('user', jsonEncode(currentUser));
        }
      }

      return decoded;
    } catch (e) {
      throw Exception('Save profile failed: $e');
    }
  }

  static Future<Map<String, dynamic>> updateProfileApi({
    String? name,
    String? phone,
  }) async {
    try {
      final headers = <String, String>{'Content-Type': 'application/json'};
      if (token != null && token!.isNotEmpty) {
        headers['Authorization'] = 'Bearer $token';
      }

      final body = <String, dynamic>{};
      if (name != null) body['name'] = name;
      if (phone != null) body['phone'] = phone;

      final resp = await http.put(
        Uri.parse('$backendBaseUrl/me'),
        headers: headers,
        body: jsonEncode(body),
      );

      if (resp.statusCode == 200 && resp.body.isNotEmpty) {
        final decoded = jsonDecode(resp.body);
        if (decoded is Map<String, dynamic>) {
          // keep local cache in sync if possible
          currentUser = decoded;
          await _prefs?.setString('user', jsonEncode(currentUser));
          return decoded;
        }
      }

      final err = resp.body.isNotEmpty ? resp.body : 'Update profile failed';
      throw Exception('Update profile failed: HTTP ${resp.statusCode} - $err');
    } catch (e) {
      throw Exception('Update profile failed: $e');
    }
  }

  static Future<bool> changePasswordApi({
    required String currentPassword,
    required String newPassword,
    required String confirmPassword,
  }) async {
    try {
      final headers = <String, String>{'Content-Type': 'application/json'};
      if (token != null && token!.isNotEmpty) {
        headers['Authorization'] = 'Bearer $token';
      }

      final body = <String, dynamic>{
        'current_password': currentPassword,
        'password': newPassword,
        'password_confirmation': confirmPassword,
      };

      final resp = await http.put(
        Uri.parse('$backendBaseUrl/auth/password'),
        headers: headers,
        body: jsonEncode(body),
      );

      if (resp.statusCode == 200) {
        return true;
      }

      String errMsg = 'Change password failed: HTTP ${resp.statusCode}';
      if (resp.body.isNotEmpty) {
        try {
          final decoded = jsonDecode(resp.body);
          if (decoded is Map && decoded['error'] is Map) {
            final e = decoded['error'] as Map;
            if (e['message'] != null) {
              errMsg = '$errMsg - ${e['message']}';
            }
          } else if (decoded is Map && decoded['message'] != null) {
            errMsg = '$errMsg - ${decoded['message']}';
          } else {
            errMsg = '$errMsg - ${resp.body}';
          }
        } catch (_) {
          errMsg = '$errMsg - ${resp.body}';
        }
      }

      throw Exception(errMsg);
    } catch (e) {
      throw Exception('Change password failed: $e');
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
      final headers = <String, String>{
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      };
      if (token != null && token!.isNotEmpty) {
        headers['Authorization'] = 'Bearer $token';
      }
      // Call unified dashboard endpoint which mirrors the web dashboard
      final resp = await http.get(
        Uri.parse('$backendBaseUrl/dashboard'),
        headers: headers,
      );

      if (resp.statusCode != 200 || resp.body.isEmpty) {
        final err = resp.body.isNotEmpty ? resp.body : 'Dashboard fetch failed';
        throw Exception(
          'Dashboard fetch failed: HTTP ${resp.statusCode} - $err',
        );
      }

      final decoded = jsonDecode(resp.body);
      final data = (decoded is Map && decoded['data'] is Map)
          ? Map<String, dynamic>.from(decoded['data'])
          : (decoded is Map<String, dynamic> ? decoded : <String, dynamic>{});

      // Map web dashboard structure to mobile-friendly fields
      Map<String, dynamic>? stats;
      final totals = data['totals'];
      if (totals is Map) {
        final overall = totals['overall'];
        if (overall is Map) {
          stats = {
            'total_income': _parseDouble(overall['income']),
            'total_expenses': _parseDouble(overall['expense']),
            'net_income': _parseDouble(overall['net']),
          };
        }
      }

      final insights = data['insights'] is List
          ? List<dynamic>.from(data['insights'] as List)
          : null;

      // Use the same recentTransactions list that the web dashboard shows
      final transactions = data['recentTransactions'] is List
          ? List<dynamic>.from(data['recentTransactions'] as List)
          : null;

      // Use activeBudgets from dashboard so status/progress match web UI
      final budgets = data['activeBudgets'] is List
          ? List<dynamic>.from(data['activeBudgets'] as List)
          : null;

      return {
        'statistics': stats,
        'insights': insights,
        'transactions': transactions,
        'budgets': budgets,
        'raw': data,
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
  // BUDGETS (CRUD via backend)
  // -------------------------
  static Future<List<dynamic>> fetchBudgets({bool activeOnly = false}) async {
    try {
      final headers = <String, String>{'Accept': 'application/json'};
      if (token != null && token!.isNotEmpty) {
        headers['Authorization'] = 'Bearer $token';
      }

      final query = <String, String>{};
      if (activeOnly) {
        query['active_only'] = '1';
      }

      final uri = Uri.parse(
        '$backendBaseUrl/budgets',
      ).replace(queryParameters: query.isEmpty ? null : query);

      final resp = await http.get(uri, headers: headers);

      if (resp.statusCode == 200 && resp.body.isNotEmpty) {
        final decoded = jsonDecode(resp.body);
        if (decoded is Map && decoded['data'] is List) {
          return List<dynamic>.from(decoded['data'] as List);
        }
      }

      final err = resp.body.isNotEmpty ? resp.body : 'Fetch budgets failed';
      throw Exception('Fetch budgets failed: HTTP ${resp.statusCode} - $err');
    } catch (e) {
      throw Exception('Fetch budgets failed: $e');
    }
  }

  static Future<Map<String, dynamic>> createBudget({
    required String name,
    required double amount,
    required String period, // weekly, monthly, quarterly, yearly
    required DateTime startDate,
    required DateTime endDate,
    int? categoryId,
    List<num>? alertThresholds,
  }) async {
    try {
      final headers = <String, String>{'Content-Type': 'application/json'};
      if (token != null && token!.isNotEmpty) {
        headers['Authorization'] = 'Bearer $token';
      }

      final body = <String, dynamic>{
        'name': name,
        'amount': amount,
        'period': period,
        'start_date': startDate.toIso8601String().split('T').first,
        'end_date': endDate.toIso8601String().split('T').first,
      };

      if (categoryId != null) {
        body['category_id'] = categoryId;
      }
      if (alertThresholds != null && alertThresholds.isNotEmpty) {
        body['alert_thresholds'] = alertThresholds;
      }

      final resp = await http.post(
        Uri.parse('$backendBaseUrl/budgets'),
        headers: headers,
        body: jsonEncode(body),
      );

      if (resp.statusCode == 201 || resp.statusCode == 200) {
        final decoded = jsonDecode(resp.body);
        if (decoded is Map && decoded['data'] is Map<String, dynamic>) {
          return Map<String, dynamic>.from(decoded['data'] as Map);
        }
        if (decoded is Map<String, dynamic>) {
          return decoded;
        }
      }

      final err = resp.body.isNotEmpty ? resp.body : 'Create budget failed';
      throw Exception('Create budget failed: HTTP ${resp.statusCode} - $err');
    } catch (e) {
      throw Exception('Create budget failed: $e');
    }
  }

  static Future<Map<String, dynamic>> updateBudget({
    required int id,
    String? name,
    double? amount,
    String? period,
    DateTime? startDate,
    DateTime? endDate,
    bool? isActive,
    int? categoryId,
    List<num>? alertThresholds,
  }) async {
    try {
      final headers = <String, String>{'Content-Type': 'application/json'};
      if (token != null && token!.isNotEmpty) {
        headers['Authorization'] = 'Bearer $token';
      }

      final body = <String, dynamic>{};
      if (name != null) body['name'] = name;
      if (amount != null) body['amount'] = amount;
      if (period != null) body['period'] = period;
      if (startDate != null) {
        body['start_date'] = startDate.toIso8601String().split('T').first;
      }
      if (endDate != null) {
        body['end_date'] = endDate.toIso8601String().split('T').first;
      }
      if (isActive != null) body['is_active'] = isActive;
      if (categoryId != null) body['category_id'] = categoryId;
      if (alertThresholds != null && alertThresholds.isNotEmpty) {
        body['alert_thresholds'] = alertThresholds;
      }

      final resp = await http.put(
        Uri.parse('$backendBaseUrl/budgets/$id'),
        headers: headers,
        body: jsonEncode(body),
      );

      if (resp.statusCode == 200 && resp.body.isNotEmpty) {
        final decoded = jsonDecode(resp.body);
        if (decoded is Map && decoded['data'] is Map<String, dynamic>) {
          return Map<String, dynamic>.from(decoded['data'] as Map);
        }
        if (decoded is Map<String, dynamic>) {
          return decoded;
        }
      }

      final err = resp.body.isNotEmpty ? resp.body : 'Update budget failed';
      throw Exception('Update budget failed: HTTP ${resp.statusCode} - $err');
    } catch (e) {
      throw Exception('Update budget failed: $e');
    }
  }

  static Future<bool> deleteBudget(int id) async {
    try {
      final headers = <String, String>{'Accept': 'application/json'};
      if (token != null && token!.isNotEmpty) {
        headers['Authorization'] = 'Bearer $token';
      }

      final resp = await http.delete(
        Uri.parse('$backendBaseUrl/budgets/$id'),
        headers: headers,
      );

      if (resp.statusCode == 200 || resp.statusCode == 204) {
        return true;
      }

      final err = resp.body.isNotEmpty ? resp.body : 'Delete budget failed';
      throw Exception('Delete budget failed: HTTP ${resp.statusCode} - $err');
    } catch (e) {
      throw Exception('Delete budget failed: $e');
    }
  }

  // -------------------------
  // REPORT SHEET (balance-style report)
  // -------------------------
  static Future<Map<String, dynamic>> fetchReportSheet({
    DateTime? startDate,
    DateTime? endDate,
  }) async {
    try {
      final headers = <String, String>{'Accept': 'application/json'};
      if (token != null && token!.isNotEmpty) {
        headers['Authorization'] = 'Bearer $token';
      }

      final query = <String, String>{'format': 'json'};
      if (startDate != null) {
        query['start_date'] = startDate.toIso8601String().split('T').first;
      }
      if (endDate != null) {
        query['end_date'] = endDate.toIso8601String().split('T').first;
      }

      final uri = Uri.parse(
        '$backendBaseUrl/reports/spending',
      ).replace(queryParameters: query);

      final resp = await http.get(uri, headers: headers);

      if (resp.statusCode != 200 || resp.body.isEmpty) {
        final err = resp.body.isNotEmpty ? resp.body : 'Report sheet failed';
        throw Exception('Report sheet failed: HTTP ${resp.statusCode} - $err');
      }

      final decoded = jsonDecode(resp.body);
      if (decoded is Map && decoded['data'] is Map<String, dynamic>) {
        return Map<String, dynamic>.from(decoded['data'] as Map);
      }
      if (decoded is Map<String, dynamic>) {
        return decoded;
      }
      return <String, dynamic>{};
    } catch (e) {
      throw Exception('Failed to fetch report sheet: $e');
    }
  }

  // -------------------------
  // RECEIPT UPLOAD (for mobile transactions)
  // -------------------------
  static Future<Map<String, dynamic>> uploadReceiptForTransaction({
    required String filename,
    required List<int> bytes,
  }) async {
    try {
      final uri = Uri.parse('$backendBaseUrl/receipts/upload');

      final headers = <String, String>{'Accept': 'application/json'};
      if (token != null && token!.isNotEmpty) {
        headers['Authorization'] = 'Bearer $token';
      }

      final request = http.MultipartRequest('POST', uri);
      request.headers.addAll(headers);

      request.files.add(
        http.MultipartFile.fromBytes('receipt', bytes, filename: filename),
      );

      final streamed = await request.send();
      final resp = await http.Response.fromStream(streamed);

      if (resp.statusCode != 200 && resp.statusCode != 201) {
        final err = resp.body.isNotEmpty ? resp.body : 'Upload receipt failed';
        throw Exception(
          'Upload receipt failed: HTTP ${resp.statusCode} - $err',
        );
      }

      if (resp.body.isEmpty) {
        throw Exception('Upload receipt failed: empty response');
      }

      final decoded = jsonDecode(resp.body);
      if (decoded is Map && decoded['data'] is Map<String, dynamic>) {
        return Map<String, dynamic>.from(decoded['data'] as Map);
      }
      if (decoded is Map && decoded['data'] is Map) {
        return Map<String, dynamic>.from(decoded['data'] as Map);
      }

      throw Exception(
        'Upload receipt failed: unexpected response ${resp.body}',
      );
    } catch (e) {
      throw Exception('Upload receipt failed: $e');
    }
  }

  // Download balance-sheet report as PDF bytes (for saving/opening on device)
  static Future<List<int>> downloadReportSheetPdf({
    DateTime? startDate,
    DateTime? endDate,
  }) async {
    try {
      final headers = <String, String>{'Accept': 'application/pdf'};
      if (token != null && token!.isNotEmpty) {
        headers['Authorization'] = 'Bearer $token';
      }

      final query = <String, String>{'format': 'pdf'};
      if (startDate != null) {
        query['start_date'] = startDate.toIso8601String().split('T').first;
      }
      if (endDate != null) {
        query['end_date'] = endDate.toIso8601String().split('T').first;
      }

      final uri = Uri.parse(
        '$backendBaseUrl/reports/report_sheet',
      ).replace(queryParameters: query);

      final resp = await http.get(uri, headers: headers);

      if (resp.statusCode != 200) {
        final err = resp.body.isNotEmpty ? resp.body : 'Report PDF failed';
        throw Exception('Report PDF failed: HTTP ${resp.statusCode} - $err');
      }

      return resp.bodyBytes;
    } catch (e) {
      throw Exception('Failed to download report PDF: $e');
    }
  }

  // -------------------------
  // SPENDING REPORT (mirrors web reports page)
  // -------------------------
  static Future<Map<String, dynamic>> fetchSpendingReport({
    required String groupBy, // 'category', 'date', or 'month'
    DateTime? startDate,
    DateTime? endDate,
    int? categoryId,
  }) async {
    try {
      final headers = <String, String>{'Accept': 'application/json'};
      if (token != null && token!.isNotEmpty) {
        headers['Authorization'] = 'Bearer $token';
      }

      final query = <String, String>{'group_by': groupBy};
      if (startDate != null) {
        query['start_date'] = startDate.toIso8601String().split('T').first;
      }
      if (endDate != null) {
        query['end_date'] = endDate.toIso8601String().split('T').first;
      }
      if (categoryId != null) {
        query['category_id'] = categoryId.toString();
      }

      final uri = Uri.parse(
        '$backendBaseUrl/reports/spending',
      ).replace(queryParameters: query);

      final resp = await http.get(uri, headers: headers);

      if (resp.statusCode != 200 || resp.body.isEmpty) {
        final err = resp.body.isNotEmpty ? resp.body : 'Spending report failed';
        throw Exception(
          'Spending report failed: HTTP ${resp.statusCode} - $err',
        );
      }

      final decoded = jsonDecode(resp.body);
      if (decoded is Map && decoded['data'] is Map<String, dynamic>) {
        return Map<String, dynamic>.from(decoded['data'] as Map);
      }
      if (decoded is Map<String, dynamic>) {
        return decoded;
      }
      return <String, dynamic>{};
    } catch (e) {
      throw Exception('Failed to fetch spending report: $e');
    }
  }

  // -------------------------
  // CREATE TRANSACTION (backend)
  // -------------------------
  static Future<bool> createTransaction({
    required int categoryId,
    double? amount,
    required String type,
    required DateTime transactionDate,
    String? description,
    int? receiptId,
  }) async {
    try {
      final headers = <String, String>{'Content-Type': 'application/json'};
      if (token != null && token!.isNotEmpty) {
        headers['Authorization'] = 'Bearer $token';
      }

      final body = <String, dynamic>{
        'category_id': categoryId,
        'transaction_date': transactionDate.toIso8601String().split('T').first,
        'type': type,
      };

      if (amount != null) {
        body['amount'] = amount;
      }

      if (description != null && description.isNotEmpty) {
        body['description'] = description;
      }

      if (receiptId != null) {
        body['receipt_id'] = receiptId;
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
  // GROUPS (via backend API)
  // -------------------------

  static Map<String, String> _authHeaders({
    String accept = 'application/json',
  }) {
    final headers = <String, String>{'Accept': accept};
    if (token != null && token!.isNotEmpty) {
      headers['Authorization'] = 'Bearer $token';
    }
    return headers;
  }

  static Future<List<dynamic>> fetchGroupsApi() async {
    try {
      final resp = await http.get(
        Uri.parse('$backendBaseUrl/groups'),
        headers: _authHeaders(),
      );

      if (resp.statusCode == 200 && resp.body.isNotEmpty) {
        final decoded = jsonDecode(resp.body);
        if (decoded is Map && decoded['data'] is List) {
          return List<dynamic>.from(decoded['data'] as List);
        }
      }

      final err = resp.body.isNotEmpty ? resp.body : 'Fetch groups failed';
      throw Exception('Fetch groups failed: HTTP ${resp.statusCode} - $err');
    } catch (e) {
      throw Exception('Fetch groups failed: $e');
    }
  }

  static Future<Map<String, dynamic>> fetchGroupApi(int groupId) async {
    try {
      final resp = await http.get(
        Uri.parse('$backendBaseUrl/groups/$groupId'),
        headers: _authHeaders(),
      );

      if (resp.statusCode == 200 && resp.body.isNotEmpty) {
        final decoded = jsonDecode(resp.body);
        if (decoded is Map && decoded['data'] is Map<String, dynamic>) {
          return Map<String, dynamic>.from(decoded['data'] as Map);
        }
        if (decoded is Map<String, dynamic>) {
          return decoded;
        }
      }

      final err = resp.body.isNotEmpty ? resp.body : 'Fetch group failed';
      throw Exception('Fetch group failed: HTTP ${resp.statusCode} - $err');
    } catch (e) {
      throw Exception('Fetch group failed: $e');
    }
  }

  static Future<List<dynamic>> fetchGroupMembersApi(int groupId) async {
    try {
      final resp = await http.get(
        Uri.parse('$backendBaseUrl/groups/$groupId/members'),
        headers: _authHeaders(),
      );

      if (resp.statusCode == 200 && resp.body.isNotEmpty) {
        final decoded = jsonDecode(resp.body);
        if (decoded is Map && decoded['data'] is List) {
          return List<dynamic>.from(decoded['data'] as List);
        }
      }

      final err = resp.body.isNotEmpty ? resp.body : 'Fetch members failed';
      throw Exception('Fetch members failed: HTTP ${resp.statusCode} - $err');
    } catch (e) {
      throw Exception('Fetch members failed: $e');
    }
  }

  static Future<List<dynamic>> fetchGroupTransactionsApi(int groupId) async {
    try {
      final resp = await http.get(
        Uri.parse('$backendBaseUrl/groups/$groupId/transactions'),
        headers: _authHeaders(),
      );

      if (resp.statusCode == 200 && resp.body.isNotEmpty) {
        final decoded = jsonDecode(resp.body);
        // endpoint returns { success, data: { group, transactions } } in many patterns;
        // accept either list or nested under data.transactions
        if (decoded is Map && decoded['data'] is Map) {
          final data = decoded['data'] as Map;
          if (data['transactions'] is List) {
            return List<dynamic>.from(data['transactions'] as List);
          }
        }
        if (decoded is Map && decoded['data'] is List) {
          return List<dynamic>.from(decoded['data'] as List);
        }
      }

      final err = resp.body.isNotEmpty
          ? resp.body
          : 'Fetch transactions failed';
      throw Exception(
        'Fetch group transactions failed: HTTP ${resp.statusCode} - $err',
      );
    } catch (e) {
      throw Exception('Fetch group transactions failed: $e');
    }
  }

  static Future<List<dynamic>> fetchAllTransactionsApi() async {
    try {
      final headers = <String, String>{
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      };
      if (token != null && token!.isNotEmpty) {
        headers['Authorization'] = 'Bearer $token';
      }

      final resp = await http.get(
        Uri.parse('$backendBaseUrl/transactions'),
        headers: headers,
      );

      if (resp.statusCode != 200 || resp.body.isEmpty) {
        final err = resp.body.isNotEmpty
            ? resp.body
            : 'Fetch transactions failed';
        throw Exception(
          'Fetch transactions failed: HTTP ${resp.statusCode} - $err',
        );
      }

      final decoded = jsonDecode(resp.body);
      final data = (decoded is Map && decoded['data'] is Map)
          ? Map<String, dynamic>.from(decoded['data'])
          : (decoded is Map<String, dynamic> ? decoded : <String, dynamic>{});

      // Handle different response formats
      if (data['data'] is List) {
        // Paginated response: data.data contains the transactions array
        final rawTransactions = List<dynamic>.from(data['data'] as List);
        // Format transactions to match dashboard format
        final formattedTransactions = rawTransactions.map((tx) {
          final transaction = Map<String, dynamic>.from(tx as Map);
          final category = transaction['category'] as Map?;
          final amount = transaction['amount'] as dynamic? ?? 0;
          final type = transaction['type'] as dynamic? ?? 'expense';
          final date = transaction['transaction_date'] as dynamic?;
          final description = transaction['description'] as dynamic? ?? '';

          // Format amount with sign
          final sign = type == 'income' ? '+' : '-';
          final displayAmount = '$sign\$${amount}';

          // Format date
          final displayDate = date != null ? _formatDate(date) : '';

          // Category name
          final categoryName = category?['name'] as String? ?? 'Category';

          return {
            ...transaction,
            'display_amount': displayAmount,
            'display_date': displayDate,
            'category_name': categoryName,
            'is_income': type == 'income',
          };
        }).toList();

        return formattedTransactions;
      } else if (data['transactions'] is List) {
        return List<dynamic>.from(data['transactions'] as List);
      } else if (decoded is List) {
        return List<dynamic>.from(decoded as List);
      } else if (data is List) {
        return List<dynamic>.from(data as List);
      } else {
        throw Exception(
          'Unexpected response format for transactions: ${resp.body}',
        );
      }
    } catch (e) {
      throw Exception('Fetch transactions failed: $e');
    }
  }

  static Future<Map<String, dynamic>> createGroupApi({
    required String name,
    required String type, // family, friends
    String? description,
    double? budgetLimit,
  }) async {
    try {
      final body = <String, dynamic>{'name': name, 'type': type};
      if (description != null && description.isNotEmpty) {
        body['description'] = description;
      }
      if (budgetLimit != null) {
        body['budget_limit'] = budgetLimit;
      }

      final resp = await http.post(
        Uri.parse('$backendBaseUrl/groups'),
        headers: {..._authHeaders(), 'Content-Type': 'application/json'},
        body: jsonEncode(body),
      );

      if (resp.statusCode == 201 || resp.statusCode == 200) {
        final decoded = jsonDecode(resp.body);
        if (decoded is Map && decoded['data'] is Map<String, dynamic>) {
          return Map<String, dynamic>.from(decoded['data'] as Map);
        }
        if (decoded is Map<String, dynamic>) {
          return decoded;
        }
      }

      final err = resp.body.isNotEmpty ? resp.body : 'Create group failed';
      throw Exception('Create group failed: HTTP ${resp.statusCode} - $err');
    } catch (e) {
      throw Exception('Create group failed: $e');
    }
  }

  static Future<Map<String, dynamic>> updateGroupApi({
    required int id,
    String? name,
    String? description,
  }) async {
    try {
      final body = <String, dynamic>{};
      if (name != null) body['name'] = name;
      if (description != null) body['description'] = description;

      final resp = await http.put(
        Uri.parse('$backendBaseUrl/groups/$id'),
        headers: {..._authHeaders(), 'Content-Type': 'application/json'},
        body: jsonEncode(body),
      );

      if (resp.statusCode == 200 && resp.body.isNotEmpty) {
        final decoded = jsonDecode(resp.body);
        if (decoded is Map && decoded['data'] is Map<String, dynamic>) {
          return Map<String, dynamic>.from(decoded['data'] as Map);
        }
        if (decoded is Map<String, dynamic>) {
          return decoded;
        }
      }

      final err = resp.body.isNotEmpty ? resp.body : 'Update group failed';
      throw Exception('Update group failed: HTTP ${resp.statusCode} - $err');
    } catch (e) {
      throw Exception('Update group failed: $e');
    }
  }

  static Future<bool> deleteGroupApi(int id) async {
    try {
      final resp = await http.delete(
        Uri.parse('$backendBaseUrl/groups/$id'),
        headers: _authHeaders(),
      );

      if (resp.statusCode == 200 || resp.statusCode == 204) {
        return true;
      }

      final err = resp.body.isNotEmpty ? resp.body : 'Delete group failed';
      throw Exception('Delete group failed: HTTP ${resp.statusCode} - $err');
    } catch (e) {
      throw Exception('Delete group failed: $e');
    }
  }

  static Future<bool> inviteGroupMemberApi({
    required int groupId,
    required String name,
    required String email,
    String? phone,
  }) async {
    try {
      final body = <String, dynamic>{'name': name, 'email': email};
      if (phone != null && phone.isNotEmpty) {
        body['phone'] = phone;
      }

      final resp = await http.post(
        Uri.parse('$backendBaseUrl/groups/$groupId/invite'),
        headers: {..._authHeaders(), 'Content-Type': 'application/json'},
        body: jsonEncode(body),
      );

      if (resp.statusCode == 200 || resp.statusCode == 201) {
        return true;
      }

      final err = resp.body.isNotEmpty ? resp.body : 'Invite member failed';
      throw Exception('Invite member failed: HTTP ${resp.statusCode} - $err');
    } catch (e) {
      throw Exception('Invite member failed: $e');
    }
  }

  static Future<bool> removeGroupMemberApi({
    required int groupId,
    required int memberId,
  }) async {
    try {
      final resp = await http.delete(
        Uri.parse('$backendBaseUrl/groups/$groupId/members/$memberId'),
        headers: _authHeaders(),
      );

      if (resp.statusCode == 200 || resp.statusCode == 204) {
        return true;
      }

      final err = resp.body.isNotEmpty ? resp.body : 'Remove member failed';
      throw Exception('Remove member failed: HTTP ${resp.statusCode} - $err');
    } catch (e) {
      throw Exception('Remove member failed: $e');
    }
  }

  // -------------------------
  // NOTIFICATIONS (backend API)
  // -------------------------
  static Future<Map<String, dynamic>> fetchNotifications({
    int page = 1,
    int perPage = 20,
    bool unreadOnly = false,
  }) async {
    try {
      final headers = _authHeaders();

      final query = <String, String>{
        'page': page.toString(),
        'per_page': perPage.toString(),
      };
      if (unreadOnly) {
        query['unread_only'] = '1';
      }

      final uri = Uri.parse(
        '$backendBaseUrl/notifications',
      ).replace(queryParameters: query);

      final resp = await http.get(uri, headers: headers);

      if (resp.statusCode == 200 && resp.body.isNotEmpty) {
        final decoded = jsonDecode(resp.body);
        if (decoded is Map && decoded['data'] is Map<String, dynamic>) {
          return Map<String, dynamic>.from(decoded['data'] as Map);
        }
        if (decoded is Map<String, dynamic>) {
          return decoded;
        }
      }

      final err = resp.body.isNotEmpty
          ? resp.body
          : 'Fetch notifications failed';
      throw Exception(
        'Fetch notifications failed: HTTP ${resp.statusCode} - $err',
      );
    } catch (e) {
      throw Exception('Fetch notifications failed: $e');
    }
  }

  static Future<int> fetchUnreadNotificationCount() async {
    try {
      final resp = await http.get(
        Uri.parse('$backendBaseUrl/notifications/unread-count'),
        headers: _authHeaders(),
      );

      if (resp.statusCode == 200 && resp.body.isNotEmpty) {
        final decoded = jsonDecode(resp.body);
        if (decoded is Map && decoded['data'] is Map) {
          final data = decoded['data'] as Map;
          final count = data['unread_count'];
          if (count is int) return count;
          if (count is num) return count.toInt();
        }
      }

      return 0;
    } catch (_) {
      return 0;
    }
  }

  static Future<bool> markNotificationAsRead(int id) async {
    try {
      final resp = await http.post(
        Uri.parse('$backendBaseUrl/notifications/$id/mark-read'),
        headers: _authHeaders(),
      );

      if (resp.statusCode == 200) return true;

      final err = resp.body.isNotEmpty ? resp.body : 'Mark read failed';
      throw Exception(
        'Mark notification read failed: HTTP ${resp.statusCode} - $err',
      );
    } catch (e) {
      throw Exception('Mark notification read failed: $e');
    }
  }

  static Future<bool> markAllNotificationsAsRead() async {
    try {
      final resp = await http.post(
        Uri.parse('$backendBaseUrl/notifications/mark-all-read'),
        headers: _authHeaders(),
      );

      if (resp.statusCode == 200) return true;

      final err = resp.body.isNotEmpty ? resp.body : 'Mark all read failed';
      throw Exception(
        'Mark all notifications read failed: HTTP ${resp.statusCode} - $err',
      );
    } catch (e) {
      throw Exception('Mark all notifications read failed: $e');
    }
  }

  // -------------------------
  // GROUP SPLIT EXPENSE (backend API)
  // -------------------------
  static Future<bool> splitGroupExpenseApi({
    required int groupId,
    required String type, // 'income' or 'expense'
    double? amount,
    String? description,
    String splitType = 'equal',
    required List<Map<String, dynamic>> splits,
    int? categoryId,
    List<int>? receiptBytes,
    String? receiptFilename,
  }) async {
    try {
      final uri = Uri.parse('$backendBaseUrl/groups/$groupId/split');

      // Create multipart request to handle receipt file upload
      final request = http.MultipartRequest('POST', uri);
      request.headers.addAll(_authHeaders());

      // Add form fields
      request.fields['type'] = type;
      request.fields['split_type'] = splitType;

      if (amount != null) {
        request.fields['amount'] = amount.toString();
      }
      if (description != null && description.isNotEmpty) {
        request.fields['description'] = description;
      }
      if (categoryId != null) {
        request.fields['category_id'] = categoryId.toString();
      }

      // Add splits in the format expected by the backend (splits[0][user_id], splits[0][amount], etc.)
      for (int i = 0; i < splits.length; i++) {
        final split = splits[i];
        request.fields['splits[$i][user_id]'] = split['user_id'].toString();
        if (splitType == 'custom' && split.containsKey('amount')) {
          request.fields['splits[$i][amount]'] = split['amount'].toString();
        } else if (splitType == 'percentage' && split.containsKey('percent')) {
          request.fields['splits[$i][percent]'] = split['percent'].toString();
        }
      }

      // Add receipt file if provided
      if (receiptBytes != null && receiptFilename != null) {
        request.files.add(
          http.MultipartFile.fromBytes(
            'receipt',
            receiptBytes,
            filename: receiptFilename,
          ),
        );
      }

      final streamedResponse = await request.send();
      final resp = await http.Response.fromStream(streamedResponse);

      if (resp.statusCode == 200 || resp.statusCode == 201) {
        return true;
      }

      final err = resp.body.isNotEmpty ? resp.body : 'Split expense failed';
      throw Exception('Split expense failed: HTTP ${resp.statusCode} - $err');
    } catch (e) {
      throw Exception('Split expense failed: $e');
    }
  }
}
