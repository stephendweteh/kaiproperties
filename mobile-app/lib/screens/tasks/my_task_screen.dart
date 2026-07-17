import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import '../../core/constants/app_colors.dart';
import '../../core/providers/auth_provider.dart';
import '../../core/providers/ticket_provider.dart';
import '../../core/widgets/task_card.dart';

const _tabs = [
  ('All', null),
  ('New', 'logged'),
  ('In Progress', 'in_progress'),
  ('Pending', 'pending_approval'),
  ('Overdue', 'overdue'),
  ('Completed', 'completed'),
  ('Closed', 'closed'),
];

class MyTaskScreen extends StatefulWidget {
  final String? initialStatus;

  const MyTaskScreen({super.key, this.initialStatus});

  const MyTaskScreen.withStatus({
    super.key,
    this.initialStatus,
  });

  @override
  State<MyTaskScreen> createState() => _MyTaskScreenState();
}

class _MyTaskScreenState extends State<MyTaskScreen>
    with SingleTickerProviderStateMixin {
  late TabController _tabCtrl;
  final _searchCtrl = TextEditingController();
  int _tabIndex = 0;

  @override
  void initState() {
    super.initState();

    final initialIndex = _tabs.indexWhere((tab) => tab.$2 == widget.initialStatus);
    _tabIndex = initialIndex >= 0 ? initialIndex : 0;

    _tabCtrl = TabController(length: _tabs.length, vsync: this);
    _tabCtrl.index = _tabIndex;
    _tabCtrl.addListener(() {
      if (!_tabCtrl.indexIsChanging) {
        setState(() => _tabIndex = _tabCtrl.index);
        _reload();
      }
    });
    WidgetsBinding.instance.addPostFrameCallback((_) => _reload());
  }

  void _reload() {
    context.read<TicketProvider>().loadTickets(
      status: _tabs[_tabIndex].$2,
      search: _searchCtrl.text.trim().isEmpty ? null : _searchCtrl.text.trim(),
    );
  }

  @override
  void dispose() {
    _tabCtrl.dispose();
    _searchCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final prov = context.watch<TicketProvider>();
    final user = context.watch<AuthProvider>().user;
    final canCreate = user?.canCreateTickets ?? false;
    return Scaffold(
      backgroundColor: AppColors.background,
      floatingActionButton: canCreate
          ? FloatingActionButton(
              backgroundColor: AppColors.primary,
              onPressed: () => context.go('/tasks/create'),
              child: const Icon(Icons.add, color: Colors.white),
            )
          : null,
      body: NestedScrollView(
        headerSliverBuilder: (_, _) => [
          SliverAppBar(
            pinned: true,
            expandedHeight: 130,
            backgroundColor: AppColors.primary,
            leading: GestureDetector(
              onTap: () => context.go('/home'),
              child: const Icon(Icons.arrow_back_ios, color: Colors.white),
            ),
            flexibleSpace: FlexibleSpaceBar(
              titlePadding: const EdgeInsets.fromLTRB(20, 0, 20, 56),
              title: const Text(
                'My Tasks',
                style: TextStyle(
                  color: Colors.white,
                  fontSize: 20,
                  fontWeight: FontWeight.bold,
                ),
              ),
            ),
            bottom: PreferredSize(
              preferredSize: const Size.fromHeight(48),
              child: TabBar(
                controller: _tabCtrl,
                isScrollable: true,
                labelColor: Colors.white,
                unselectedLabelColor: Colors.white54,
                indicatorColor: AppColors.accent,
                indicatorWeight: 3,
                labelStyle: const TextStyle(
                  fontWeight: FontWeight.w600,
                  fontSize: 12,
                ),
                tabs: _tabs.map((t) => Tab(text: t.$1)).toList(),
              ),
            ),
          ),
          SliverToBoxAdapter(
            child: Padding(
              padding: const EdgeInsets.fromLTRB(16, 12, 16, 4),
              child: TextField(
                controller: _searchCtrl,
                onChanged: (_) => _reload(),
                decoration: InputDecoration(
                  hintText: 'Search tasks…',
                  prefixIcon: const Icon(
                    Icons.search,
                    color: AppColors.primary,
                    size: 20,
                  ),
                  filled: true,
                  fillColor: Colors.white,
                  contentPadding: const EdgeInsets.symmetric(vertical: 10),
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12),
                    borderSide: BorderSide.none,
                  ),
                  hintStyle: const TextStyle(
                    color: AppColors.textLight,
                    fontSize: 13,
                  ),
                ),
              ),
            ),
          ),
        ],
        body: prov.loading
            ? const Center(
                child: CircularProgressIndicator(color: AppColors.primary),
              )
            : prov.error != null
            ? _buildError(prov)
            : prov.tickets.isEmpty
            ? _buildEmpty()
            : _buildList(prov),
      ),
    );
  }

  Widget _buildError(TicketProvider prov) {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          const Icon(Icons.error_outline, color: AppColors.error, size: 48),
          const SizedBox(height: 12),
          Text(
            prov.error!,
            style: const TextStyle(color: AppColors.textSecondary),
          ),
          const SizedBox(height: 16),
          ElevatedButton(
            onPressed: _reload,
            style: ElevatedButton.styleFrom(backgroundColor: AppColors.primary),
            child: const Text('Retry', style: TextStyle(color: Colors.white)),
          ),
        ],
      ),
    );
  }

  Widget _buildEmpty() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          const Icon(
            Icons.inbox_outlined,
            color: AppColors.textLight,
            size: 56,
          ),
          const SizedBox(height: 12),
          const Text(
            'No tasks found',
            style: TextStyle(color: AppColors.textSecondary, fontSize: 15),
          ),
        ],
      ),
    );
  }

  Widget _buildList(TicketProvider prov) {
    return RefreshIndicator(
      color: AppColors.primary,
      onRefresh: () async => _reload(),
      child: NotificationListener<ScrollNotification>(
        onNotification: (n) {
          if (n.metrics.pixels >= n.metrics.maxScrollExtent - 100 &&
              !prov.loadingMore) {
            prov.loadMore();
          }
          return false;
        },
        child: ListView.builder(
          padding: const EdgeInsets.only(top: 8, bottom: 80),
          itemCount: prov.tickets.length + (prov.loadingMore ? 1 : 0),
          itemBuilder: (_, i) {
            if (i == prov.tickets.length) {
              return const Center(
                child: Padding(
                  padding: EdgeInsets.all(16),
                  child: CircularProgressIndicator(
                    strokeWidth: 2,
                    color: AppColors.primary,
                  ),
                ),
              );
            }
            final t = prov.tickets[i];
            return TaskCard(
              ticket: t,
              onTap: () => context.go('/tasks/${t.id}'),
            );
          },
        ),
      ),
    );
  }
}
