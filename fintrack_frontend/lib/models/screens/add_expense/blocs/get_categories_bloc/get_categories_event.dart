import 'package:equatable/equatable.dart';

sealed class GetCategoriesEvent extends Equatable {
  const GetCategoriesEvent();

  @override
  List<Object?> get props => [];
}

final class GetCategories extends GetCategoriesEvent {}
