import 'dart:typed_data';

import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:intl/intl.dart';

import '../../../services/api_service.dart';
import '../../notifications/views/notifications_screen.dart';
import 'change_password_screen.dart';

class ProfileScreen extends StatefulWidget {
  const ProfileScreen({super.key});

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  bool _loading = true;
  bool _saving = false;
  String? _error;

  String? _avatarUrl;
  String? _status;
  String? _joinedText;

  Uint8List? _pendingAvatarBytes;
  String? _pendingAvatarFileName;

  final TextEditingController _nameCtrl = TextEditingController();
  final TextEditingController _emailCtrl = TextEditingController();
  final TextEditingController _phoneCtrl = TextEditingController();

  int _unreadCount = 0;
  final ImagePicker _imagePicker = ImagePicker();

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final user = await ApiService.fetchCurrentUser();
      _nameCtrl.text = user['name']?.toString() ?? '';
      _emailCtrl.text = user['email']?.toString() ?? '';
      _phoneCtrl.text = user['phone']?.toString() ?? '';

      _avatarUrl = user['avatar']?.toString();
      _status = user['status']?.toString();

      final createdAtRaw = user['created_at']?.toString();
      if (createdAtRaw != null && createdAtRaw.isNotEmpty) {
        try {
          final parsed = DateTime.tryParse(createdAtRaw);
          if (parsed != null) {
            _joinedText = DateFormat('dd MMM yyyy').format(parsed);
          } else {
            _joinedText = createdAtRaw;
          }
        } catch (_) {
          _joinedText = createdAtRaw;
        }
      } else {
        _joinedText = null;
      }

      final unread = await ApiService.fetchUnreadNotificationCount();
      setState(() {
        _unreadCount = unread;
      });
    } catch (e) {
      setState(() {
        _error = e.toString();
      });
    } finally {
      setState(() {
        _loading = false;
      });
    }
  }

  Future<void> _save() async {
    setState(() {
      _saving = true;
      _error = null;
    });
    try {
      final result = await ApiService.saveProfileWithAvatar(
        name: _nameCtrl.text.trim(),
        phone: _phoneCtrl.text.trim().isEmpty ? null : _phoneCtrl.text.trim(),
        avatarBytes: _pendingAvatarBytes,
        avatarFilename: _pendingAvatarFileName,
      );

      // Clear pending avatar after successful save
      _pendingAvatarBytes = null;
      _pendingAvatarFileName = null;

      // Update local avatar URL from response if present
      final avatarUrl =
          (result['avatar_url'] is String &&
              (result['avatar_url'] as String).isNotEmpty)
          ? result['avatar_url'] as String
          : (result['user'] is Map &&
                    (result['user'] as Map)['avatar'] is String
                ? (result['user'] as Map)['avatar'] as String
                : _avatarUrl);

      _avatarUrl = avatarUrl;

      if (!mounted) return;
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(const SnackBar(content: Text('Profile updated')));
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

  void _logout() {
    showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Logout'),
        content: const Text('Are you sure you want to log out?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: const Text('Cancel'),
          ),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Logout'),
          ),
        ],
      ),
    ).then((confirm) async {
      if (confirm != true) return;
      await ApiService.logout();
      if (!mounted) return;
      Navigator.of(
        context,
      ).pushNamedAndRemoveUntil('/login', (Route<dynamic> _) => false);
    });
  }

  void _openNotifications() {
    Navigator.push<bool>(
      context,
      MaterialPageRoute(builder: (_) => const NotificationsScreen()),
    ).then((changed) async {
      if (changed == true) {
        final unread = await ApiService.fetchUnreadNotificationCount();
        if (!mounted) return;
        setState(() {
          _unreadCount = unread;
        });
      }
    });
  }

  Future<void> _changeAvatar() async {
    final picked = await _imagePicker.pickImage(
      source: ImageSource.gallery,
      imageQuality: 80,
    );
    if (picked == null) return;
    try {
      final bytes = await picked.readAsBytes();
      setState(() {
        _pendingAvatarBytes = bytes;
        _pendingAvatarFileName = picked.name.isNotEmpty
            ? picked.name
            : 'avatar_${DateTime.now().millisecondsSinceEpoch}.jpg';
      });

      if (!mounted) return;
      setState(() {
        // _error = e.toString();
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

    return Scaffold(
      appBar: AppBar(title: const Text('Profile & Settings')),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: _load,
              child: ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  if (_error != null) ...[
                    Text(
                      _error!,
                      style: theme.textTheme.bodyMedium?.copyWith(
                        color: theme.colorScheme.error,
                      ),
                    ),
                    const SizedBox(height: 12),
                  ],

                  /// Avatar + Name
                  Row(
                    children: [
                      GestureDetector(
                        onTap: _changeAvatar,
                        child: Stack(
                          alignment: Alignment.bottomRight,
                          children: [
                            CircleAvatar(
                              radius: 28,
                              backgroundImage: _pendingAvatarBytes != null
                                  ? MemoryImage(_pendingAvatarBytes!)
                                  : (_avatarUrl?.isNotEmpty == true
                                        ? NetworkImage(_avatarUrl!)
                                        : null),
                              child:
                                  _pendingAvatarBytes == null &&
                                      _avatarUrl == null
                                  ? Text(
                                      (_nameCtrl.text.isNotEmpty
                                              ? _nameCtrl.text[0]
                                              : 'U')
                                          .toUpperCase(),
                                    )
                                  : null,
                            ),
                            Container(
                              padding: const EdgeInsets.all(4),
                              decoration: BoxDecoration(
                                color: theme.colorScheme.primary,
                                shape: BoxShape.circle,
                              ),
                              child: const Icon(
                                Icons.camera_alt,
                                size: 14,
                                color: Colors.white,
                              ),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(width: 16),
                      Expanded(
                        child: Column(
                          children: [
                            TextField(
                              controller: _nameCtrl,
                              decoration: const InputDecoration(
                                labelText: 'Name',
                              ),
                            ),
                            const SizedBox(height: 8),
                            TextField(
                              controller: _emailCtrl,
                              enabled: false,
                              decoration: const InputDecoration(
                                labelText: 'Email',
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),

                  const SizedBox(height: 12),

                  if (_joinedText != null || _status?.isNotEmpty == true)
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        if (_joinedText != null)
                          Row(
                            children: [
                              const Icon(Icons.calendar_today, size: 16),
                              const SizedBox(width: 8),
                              Text('Joined: $_joinedText'),
                            ],
                          ),
                        if (_status?.isNotEmpty == true) ...[
                          const SizedBox(height: 4),
                          Row(
                            children: [
                              const Icon(Icons.verified_user, size: 16),
                              const SizedBox(width: 8),
                              Text('Status: $_status'),
                            ],
                          ),
                        ],
                      ],
                    ),

                  const SizedBox(height: 16),

                  TextField(
                    controller: _phoneCtrl,
                    keyboardType: TextInputType.phone,
                    decoration: const InputDecoration(
                      labelText: 'Phone (optional)',
                    ),
                  ),

                  const SizedBox(height: 16),

                  ElevatedButton(
                    onPressed: _saving ? null : _save,
                    child: _saving
                        ? const SizedBox(
                            width: 18,
                            height: 18,
                            child: CircularProgressIndicator(strokeWidth: 2),
                          )
                        : const Text('Save changes'),
                  ),

                  const SizedBox(height: 24),

                  ListTile(
                    leading: const Icon(Icons.notifications_outlined),
                    title: const Text('Notifications'),
                    trailing: _unreadCount > 0
                        ? CircleAvatar(
                            radius: 10,
                            backgroundColor: theme.colorScheme.error,
                            child: Text(
                              '$_unreadCount',
                              style: const TextStyle(
                                fontSize: 11,
                                color: Colors.white,
                              ),
                            ),
                          )
                        : null,
                    onTap: _openNotifications,
                  ),

                  const Divider(),

                  ListTile(
                    leading: const Icon(Icons.lock_outline),
                    title: const Text('Change password'),
                    trailing: const Icon(Icons.chevron_right),
                    onTap: () {
                      Navigator.of(context).push(
                        MaterialPageRoute(
                          builder: (_) => const ChangePasswordScreen(),
                        ),
                      );
                    },
                  ),

                  const Divider(),

                  ListTile(
                    leading: const Icon(Icons.logout, color: Colors.redAccent),
                    title: const Text('Logout'),
                    onTap: _logout,
                  ),
                ],
              ),
            ),
    );
  }
}
