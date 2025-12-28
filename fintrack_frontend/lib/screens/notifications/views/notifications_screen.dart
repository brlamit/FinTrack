import 'package:flutter/material.dart';

import '../../../services/api_service.dart';

class NotificationsScreen extends StatefulWidget {
  const NotificationsScreen({super.key});

  @override
  State<NotificationsScreen> createState() => _NotificationsScreenState();
}

class _NotificationsScreenState extends State<NotificationsScreen> {
  bool _loading = true;
  String? _error;
  List<dynamic> _items = [];

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
      final page = await ApiService.fetchNotifications();
      final data = page['data'];
      if (data is List) {
        setState(() {
          _items = List<dynamic>.from(data);
        });
      } else {
        setState(() {
          _items = [];
        });
      }
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

  Future<void> _markAllRead() async {
    try {
      await ApiService.markAllNotificationsAsRead();
      await _load();
      if (!mounted) return;
      Navigator.pop(context, true);
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text('Failed to mark all as read: $e')));
    }
  }

  Future<void> _markOneRead(int id) async {
    try {
      await ApiService.markNotificationAsRead(id);
      await _load();
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text('Failed to mark as read: $e')));
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Notifications'),
        actions: [
          IconButton(
            onPressed: _items.isEmpty ? null : _markAllRead,
            icon: const Icon(Icons.done_all),
            tooltip: 'Mark all as read',
          ),
        ],
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: _load,
              child: _error != null
                  ? ListView(
                      padding: const EdgeInsets.all(16.0),
                      children: [
                        Text(
                          'Failed to load notifications',
                          style: theme.textTheme.bodyMedium?.copyWith(
                            color: theme.colorScheme.error,
                          ),
                        ),
                        const SizedBox(height: 8),
                        Text(_error!, style: theme.textTheme.bodySmall),
                      ],
                    )
                  : _items.isEmpty
                  ? ListView(
                      padding: const EdgeInsets.all(16.0),
                      children: const [Text('No notifications yet.')],
                    )
                  : ListView.separated(
                      itemCount: _items.length,
                      separatorBuilder: (_, __) => const Divider(height: 0),
                      itemBuilder: (context, index) {
                        final n = _items[index];
                        if (n is! Map) return const SizedBox.shrink();
                        final id = n['id'] as int?;
                        final title = n['title']?.toString() ?? 'Notification';
                        final message = n['message']?.toString() ?? '';
                        final createdAt = n['created_at']?.toString();
                        final isRead = (n['is_read'] ?? false) as bool;

                        return ListTile(
                          title: Text(title),
                          subtitle: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              if (message.isNotEmpty) Text(message),
                              if (createdAt != null)
                                Text(
                                  createdAt,
                                  style: theme.textTheme.bodySmall?.copyWith(
                                    color: theme.colorScheme.onSurface
                                        .withOpacity(0.6),
                                  ),
                                ),
                            ],
                          ),
                          trailing: isRead
                              ? const Icon(
                                  Icons.mark_email_read,
                                  color: Colors.green,
                                )
                              : IconButton(
                                  icon: const Icon(Icons.mark_email_unread),
                                  onPressed: id == null
                                      ? null
                                      : () => _markOneRead(id),
                                ),
                        );
                      },
                    ),
            ),
    );
  }
}
