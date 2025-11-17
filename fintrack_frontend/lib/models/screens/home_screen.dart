import 'package:flutter/material.dart';
import '/services/api_service.dart';
import '/widgets/app_drawer.dart';

class HomeScreen extends StatelessWidget {
  static const routeName = '/home';
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('FinTrack')),
      drawer: AppDrawer(onLogout: () => Navigator.of(context).pushReplacementNamed('/login')),
      body: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Welcome to FinTrack', style: Theme.of(context).textTheme.headlineMedium),
            SizedBox(height: 8),
            Text('Quick actions', style: Theme.of(context).textTheme.titleMedium),
            SizedBox(height: 12),
            Row(
              children: [
                Expanded(
                  child: ElevatedButton.icon(
                    icon: Icon(Icons.group),
                    label: Text('My Groups'),
                    onPressed: () => Navigator.of(context).pushNamed('/groups'),
                  ),
                ),
                SizedBox(width: 8),
                Expanded(
                  child: ElevatedButton.icon(
                    icon: Icon(Icons.add),
                    label: Text('Create'),
                    onPressed: () => ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Create group (not implemented)'))),
                  ),
                ),
              ],
            ),
            SizedBox(height: 16),
            Expanded(
              child: FutureBuilder<List<dynamic>>(
                future: ApiService.fetchGroups(),
                builder: (context, snapshot) {
                  if (snapshot.connectionState != ConnectionState.done) return Center(child: CircularProgressIndicator());
                  if (snapshot.hasError) return Center(child: Text('Error: ' + snapshot.error.toString()));
                  final groups = snapshot.data ?? [];
                  if (groups.isEmpty) return Center(child: Text('No groups yet'));
                  return ListView.builder(
                    itemCount: groups.length,
                    itemBuilder: (context, index) {
                      final g = groups[index];
                      return Card(
                        child: ListTile(
                          title: Text(g['name'] ?? 'Group'),
                          subtitle: Text('Members: ${g['members_count'] ?? (g['members']?.length ?? 0)}'),
                          /* onTap: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => GroupDetailScreen(groupId: g['id']))), */
                        ),
                      );
                    },
                  );
                },
              ),
            )
          ],
        ),
      ),
    );
  }
}
