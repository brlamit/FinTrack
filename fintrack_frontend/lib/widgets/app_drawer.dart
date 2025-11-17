import 'package:flutter/material.dart';
import '/services/api_service.dart';

class AppDrawer extends StatelessWidget {
  final VoidCallback onLogout;
  AppDrawer({required this.onLogout});

  @override
  Widget build(BuildContext context) {
    return Drawer(
      child: Column(
        children: [
          UserAccountsDrawerHeader(
            accountName: Text(ApiService.isLoggedIn ? 'User' : 'Guest'),
            accountEmail: Text(''),
            currentAccountPicture: CircleAvatar(child: Icon(Icons.person)),
          ),
          ListTile(
            leading: Icon(Icons.home),
            title: Text('Home'),
            onTap: () => Navigator.of(context).pushReplacementNamed('/'),
          ),
          ListTile(
            leading: Icon(Icons.group),
            title: Text('Groups'),
            onTap: () => Navigator.of(context).pushReplacementNamed('/groups'),
          ),
          ListTile(
            leading: Icon(Icons.person),
            title: Text('Profile'),
            // onTap: () => Navigator.of(context).pushNamed(ProfileScreen.routeName),
          ),
          ListTile(
            leading: Icon(Icons.settings),
            title: Text('Settings'),
            // onTap: () => Navigator.of(context).pushNamed(SettingsScreen.routeName),
          ),
          Spacer(),
          ListTile(
            leading: Icon(Icons.logout),
            title: Text('Logout'),
            onTap: () async {
              await ApiService.logout();
              onLogout();
            },
          ),
        ],
      ),
    );
  }
}
