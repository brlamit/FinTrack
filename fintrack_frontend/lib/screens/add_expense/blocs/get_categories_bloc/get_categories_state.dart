import 'package:equatable/equatable.dart';

sealed class GetCategoriesState extends Equatable {
  const GetCategoriesState();

  @override
  List<Object?> get props => [];
}

final class GetCategoriesInitial extends GetCategoriesState {}

final class GetCategoriesLoaded extends GetCategoriesState {}

final class GetCategoriesFailure extends GetCategoriesState {}
